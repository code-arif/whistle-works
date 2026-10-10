<?php

namespace Modules\Director\app\Services\Ai;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\AiSetting;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Director\app\Services\Ai\Contracts\AiProviderInterface;
use Modules\Director\app\Services\Ai\Providers\GeminiProvider;
use Modules\Director\app\Services\Ai\Providers\OpenAiProvider;

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
}
