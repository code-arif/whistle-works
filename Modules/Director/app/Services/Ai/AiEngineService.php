<?php

namespace Modules\Director\app\Services\Ai;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\AiSetting;
use App\Models\CampEvaluatorRegistration;
use App\Models\CampRefereeJearsyNumber;
use App\Models\RefereeEvaluation;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Director\app\Services\Ai\Contracts\AiProviderInterface;
use Modules\Director\app\Services\Ai\Providers\GeminiProvider;
use Modules\Director\app\Services\Ai\Providers\OpenAiProvider;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;

class AiEngineService
{
    protected AiToolRegistry $toolRegistry;
    protected ?AiProviderInterface $provider = null;

    public function __construct(AiToolRegistry $toolRegistry)
    {
        $this->toolRegistry = $toolRegistry;
    }

    /**
     * Resolve the active AI Provider based on database settings or env fallback.
     */
    public function getProvider(): AiProviderInterface
    {
        if ($this->provider !== null) {
            return $this->provider;
        }

        $providerName = strtolower(AiSetting::getValue('ai_provider', 'openai'));

        if ($providerName === 'gemini') {
            $apiKey = AiSetting::getValue('gemini_api_key', env('GEMINI_API_KEY', ''));
            $model = AiSetting::getValue('gemini_model', env('GEMINI_MODEL', 'gemini-1.5-pro'));
            $this->provider = new GeminiProvider($apiKey, $model);
        } else {
            $apiKey = AiSetting::getValue('openai_api_key', env('OPENAI_API_KEY', ''));
            $model = AiSetting::getValue('openai_model', env('OPENAI_MODEL', 'gpt-4o'));
            $this->provider = new OpenAiProvider($apiKey, $model);
        }

        return $this->provider;
    }

    /**
     * Build the foundational system prompt for the Whistle Works AI Coach & Analytics Engine.
     */
    public function getSystemPrompt(): string
    {
        $customPrompt = AiSetting::getValue('ai_system_prompt_override');
        if (!empty($customPrompt)) {
            return $customPrompt;
        }

        return <<<PROMPT
You are the Whistle Works AI Coach and Advanced Analytics Assistant for Camp Directors and Staff.
Whistle Works is an enterprise platform managing sports officiating camps, referee evaluations, game schedules, crew assignments, and evaluator grading.

YOUR CAPABILITIES & RULES:
1. Deterministic Accuracy: Whenever answering questions about referee performance, camp rosters, scores, rankings, or evaluator activity, you MUST call the appropriate function tool to fetch live database records. Do not fabricate names, scores, or statistics.
2. UI Visualizations & Charts: When users ask to "show in a line graph", "compare referees", "plot progress", or "trend over time", invoke the tool that produces line chart or radar graph widgets. The client UI renders these widgets interactively.
3. Excel Exports: When users request spreadsheets (e.g. "compile rankings into excel", "export report"), call the spreadsheet compilation tool and provide a direct download link.
4. Tone & Style: Be professional, insightful, and concise. Act like an experienced officiating director and performance analyst. Format your text answers with clean GitHub Markdown (bullet points, bold key stats, and markdown summary tables where helpful).
5. Officiating Evaluation Criteria: Standard evaluation criteria in Whistle Works include: Accuracy, Communication, Consistency, Mechanics, Fitness, and Game Awareness (graded 1 to 10).
PROMPT;
    }

    /**
     * Process a user prompt through the multi-turn session and tool execution lifecycle.
     */
    public function processUserMessage(User $user, string $userPrompt, ?string $sessionUuid = null): array
    {
        // 1. Resolve or create chat session
        $session = null;
        if (!empty($sessionUuid)) {
            $session = AiChatSession::where('session_uuid', $sessionUuid)
                ->where('user_id', $user->id)
                ->first();
        }

        if (!$session) {
            $title = Str::limit(trim($userPrompt), 60);
            $session = AiChatSession::create([
                'user_id' => $user->id,
                'title' => $title,
                'last_interaction_at' => Carbon::now(),
            ]);
        } elseif ($session->title === 'New Conversation' || empty($session->title)) {
            $session->update([
                'title' => Str::limit(trim($userPrompt), 60),
            ]);
        }

        // 2. Persist incoming user message
        AiChatMessage::create([
            'session_id' => $session->id,
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $userPrompt,
        ]);

        // 3. Build conversation context from recent history (last 15 turns)
        $history = AiChatMessage::where('session_id', $session->id)
            ->orderBy('id', 'desc')
            ->limit(15)
            ->get()
            ->reverse();

        $messages = [];
        $messages[] = [
            'role' => 'system',
            'content' => $this->getSystemPrompt(),
        ];

        foreach ($history as $msg) {
            $formattedMsg = [
                'role' => $msg->role,
                'content' => $msg->content ?? '',
            ];
            if (!empty($msg->tools_called)) {
                $formattedMsg['tool_calls'] = $msg->tools_called;
            }
            $messages[] = $formattedMsg;
        }

        // 4. Resolve provider and tools schema
        $provider = $this->getProvider();
        $toolsSchema = $this->toolRegistry->getToolsSchema();

        $totalTokensAccumulated = 0;
        $toolsExecuted = [];
        $widgetType = null;
        $widgetPayload = null;

        // 5. Initial LLM Dispatch
        $response = $provider->chat($messages, $toolsSchema);
        $totalTokensAccumulated += ($response['tokens_used'] ?? 0);

        // 6. Handle Tool Calling Execution Loop
        if (!empty($response['tool_calls'])) {
            $assistantMsgWithToolCalls = [
                'role' => 'assistant',
                'content' => $response['content'] ?? '',
                'tool_calls' => array_map(function ($tc) {
                    $item = [
                        'id' => $tc['id'],
                        'type' => 'function',
                        'function' => [
                            'name' => $tc['name'],
                            'arguments' => $tc['raw_arguments'] ?? json_encode($tc['arguments']),
                        ],
                    ];
                    if (!empty($tc['thought_signature'])) {
                        $item['thought_signature'] = $tc['thought_signature'];
                    }
                    return $item;
                }, $response['tool_calls']),
            ];
            $messages[] = $assistantMsgWithToolCalls;

            foreach ($response['tool_calls'] as $toolCall) {
                $toolName = $toolCall['name'];
                $toolArgs = $toolCall['arguments'] ?? [];
                $toolCallId = $toolCall['id'];

                $toolsExecuted[] = $toolName;

                // Execute tool via registry
                $execResult = $this->toolRegistry->executeTool($toolName, $toolArgs, $user->id);

                if (!empty($execResult['widget_type'])) {
                    $widgetType = $execResult['widget_type'];
                    $widgetPayload = $execResult['widget_payload'];
                }

                $toolOutputString = json_encode($execResult['result'] ?? $execResult);

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCallId,
                    'tool_name' => $toolName,
                    'content' => $toolOutputString,
                ];
            }

            // Follow-up LLM completion to synthesize final conversational response
            $finalResponse = $provider->chat($messages, []);
            $totalTokensAccumulated += ($finalResponse['tokens_used'] ?? 0);
            $assistantContent = $finalResponse['content'] ?? 'Here are the requested analytics.';
        } else {
            $assistantContent = $response['content'] ?? 'I was unable to process your request.';
        }

        // 7. Persist assistant response
        AiChatMessage::create([
            'session_id' => $session->id,
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => $assistantContent,
            'widget_type' => $widgetType,
            'widget_payload' => $widgetPayload,
            'tokens_used' => $totalTokensAccumulated,
            'tools_called' => !empty($toolsExecuted) ? $toolsExecuted : null,
        ]);

        // 8. Update Session Metrics
        $session->recordInteraction($totalTokensAccumulated);

        // 9. Update User Quotas
        $userQuota = $user->getOrCreateAiQuota();
        $userQuota->recordUsage($totalTokensAccumulated);

        return [
            'session_uuid' => $session->session_uuid,
            'session_title' => $session->title,
            'role' => 'assistant',
            'content' => $assistantContent,
            'widget_type' => $widgetType,
            'widget_payload' => $widgetPayload,
            'tokens_used' => $totalTokensAccumulated,
            'tools_called' => $toolsExecuted,
            'quota' => [
                'remaining_queries' => $userQuota->fresh()->remaining_queries,
                'monthly_limit' => $userQuota->monthly_query_limit,
                'queries_used_this_month' => $userQuota->fresh()->queries_used_this_month,
                'plan_tier' => $userQuota->plan_tier,
            ],
        ];
    }

    /**
     * Retrieve lightweight camps directory with referees & evaluators
     * for AI mention autocomplete and context intelligence.
     *
     * @param User $user Authenticated director
     * @return array
     */
    public function getDirectorCampsDirectory(User $user): array
    {
        // 1. Fetch all camps for this director (active, upcoming, and previous)
        $camps = Camp::forDirectorOrAssistant($user->id)
            ->select(['id', 'director_id', 'camp_name', 'camp_logo', 'start_date', 'end_date'])
            ->orderBy('start_date', 'desc')
            ->get();

        if ($camps->isEmpty()) {
            return [];
        }

        $campIds = $camps->pluck('id')->all();

        // 2. Bulk load referees (from checkins and jersey number assignments)
        $checkinRefereesByCamp = CampRefereeCheckin::whereIn('camp_id', $campIds)
            ->whereIn('registration_status', ['registered', 'checked_in'])
            ->with(['referee:id,first_name,last_name,avatar'])
            ->get()
            ->groupBy('camp_id');

        $jerseyRefereesByCamp = CampRefereeJearsyNumber::whereIn('camp_id', $campIds)
            ->with(['referee:id,first_name,last_name,avatar'])
            ->get()
            ->groupBy('camp_id');

        // 3. Bulk load evaluators (from registrations and evaluations)
        $evaluatorRegistrationsByCamp = CampEvaluatorRegistration::whereIn('camp_id', $campIds)
            ->where('status', '!=', 'rejected')
            ->with(['evaluator:id,first_name,last_name,avatar'])
            ->get()
            ->groupBy('camp_id');

        $evaluationsByCamp = RefereeEvaluation::whereIn('camp_id', $campIds)
            ->with(['evaluator:id,first_name,last_name,avatar'])
            ->get()
            ->groupBy('camp_id');

        // Formatter & sorter for person objects (sorted alphabetically by last name)
        $formatPerson = function ($person) {
            if (!$person) {
                return null;
            }

            $fullName = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
            if ($fullName === '') {
                $fullName = $person->username ?? "User #{$person->id}";
            }

            $avatarUrl = null;
            if (!empty($person->avatar)) {
                $avatarUrl = filter_var($person->avatar, FILTER_VALIDATE_URL)
                    ? $person->avatar
                    : asset($person->avatar);
            } else {
                $avatarUrl = asset('default/profile.jpg');
            }

            $lastName = trim($person->last_name ?? '');
            $firstName = trim($person->first_name ?? $person->name ?? '');

            return [
                'id' => (int) $person->id,
                'name' => $fullName,
                'avatar' => $avatarUrl,
                '_last_name' => strtolower($lastName),
                '_first_name' => strtolower($firstName),
            ];
        };

        $sortPeople = function ($peopleCollection) use ($formatPerson) {
            return $peopleCollection
                ->map($formatPerson)
                ->filter()
                ->unique('id')
                ->sort(function ($a, $b) {
                    $lastA = $a['_last_name'];
                    $lastB = $b['_last_name'];

                    if ($lastA !== '' && $lastB !== '') {
                        $cmp = strcmp($lastA, $lastB);
                        if ($cmp !== 0) {
                            return $cmp;
                        }
                    } elseif ($lastA !== '' && $lastB === '') {
                        return -1;
                    } elseif ($lastA === '' && $lastB !== '') {
                        return 1;
                    }

                    return strcmp($a['_first_name'], $b['_first_name']);
                })
                ->values()
                ->map(function ($item) {
                    unset($item['_last_name'], $item['_first_name']);
                    return $item;
                })
                ->all();
        };

        $result = [];

        foreach ($camps as $camp) {
            $logoUrl = null;
            if (!empty($camp->camp_logo)) {
                $logoUrl = filter_var($camp->camp_logo, FILTER_VALIDATE_URL)
                    ? $camp->camp_logo
                    : asset($camp->camp_logo);
            } else {
                $logoUrl = asset('default/no_image.webp');
            }

            // Referees
            $campCheckins = $checkinRefereesByCamp->get($camp->id, collect());
            $campJerseys = $jerseyRefereesByCamp->get($camp->id, collect());
            $rawReferees = $campCheckins->pluck('referee')
                ->merge($campJerseys->pluck('referee'))
                ->filter();
            $formattedReferees = $sortPeople($rawReferees);

            // Evaluators
            $campEvaluatorRegs = $evaluatorRegistrationsByCamp->get($camp->id, collect());
            $campEvaluations = $evaluationsByCamp->get($camp->id, collect());
            $rawEvaluators = $campEvaluatorRegs->pluck('evaluator')
                ->merge($campEvaluations->pluck('evaluator'))
                ->filter();
            $formattedEvaluators = $sortPeople($rawEvaluators);

            $result[] = [
                'id' => (int) $camp->id,
                'name' => (string) ($camp->camp_name ?? "Camp #{$camp->id}"),
                'logo' => $logoUrl,
                'referees' => $formattedReferees,
                'evaluators' => $formattedEvaluators,
            ];
        }

        return $result;
    }
}
