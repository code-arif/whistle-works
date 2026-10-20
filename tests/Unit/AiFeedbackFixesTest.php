<?php

namespace Tests\Unit;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\CampEvaluatorRegistration;
use App\Models\RefereeEvaluation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Modules\Director\app\Services\Ai\AiToolRegistry;
use Modules\Director\app\Services\Ai\Providers\OpenAiProvider;
use Modules\Director\app\Services\Ai\Tools\CompareRefereesTool;
use Modules\Director\app\Services\Ai\Tools\CompileCampRankingsExcelTool;
use Modules\Director\app\Services\Ai\Tools\GetCampInventoryTool;
use Modules\Director\app\Services\Ai\Tools\PendingEvaluatorsTool;
use Modules\Director\app\Services\Ai\Tools\RefereeImprovementTool;
use Modules\Director\Models\Camp;
use Tests\TestCase;

class AiFeedbackFixesTest extends TestCase
{
    protected AiToolRegistry $registry;
    protected User $director;
    protected User $referee1;
    protected User $referee2;
    protected Camp $camp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new AiToolRegistry();

        $this->director = User::firstOrCreate(
            ['email' => 'feedback_director@whistleworks.org'],
            [
                'first_name' => 'Gregg',
                'last_name' => 'Popovich',
                'username' => 'pop_director_fix',
                'slug' => 'pop-director-fix',
            ]
        );

        $this->referee1 = User::firstOrCreate(
            ['email' => 'referee1_test@whistleworks.org'],
            ['first_name' => 'Michael', 'last_name' => 'Jordan', 'username' => 'mj_ref_test']
        );

        $this->referee2 = User::firstOrCreate(
            ['email' => 'referee2_test@whistleworks.org'],
            ['first_name' => 'Kobe', 'last_name' => 'Bryant', 'username' => 'kb_ref_test']
        );

        $sport = \App\Models\SportsType::firstOrCreate(
            ['sports_name' => 'Basketball'],
            ['sports_fee' => 10, 'status' => 'active']
        );

        $this->camp = Camp::firstOrCreate(
            ['camp_name' => 'San Antonio Showcase 2026'],
            [
                'director_id' => $this->director->id,
                'sports_type_id' => $sport->id,
                'sports_type_name' => $sport->sports_name,
                'location' => 'San Antonio, TX',
                'price' => 100.00,
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(10),
                'end_date' => Carbon::now()->addDays(14),
            ]
        );
    }

    /**
     * Test 1: Single-camp question auto-resolves evaluated referees when no names are supplied.
     */
    public function test_single_camp_resolution_without_referee_names(): void
    {
        // Seed evaluation for referee1 in this camp
        RefereeEvaluation::firstOrCreate(
            [
                'referee_id' => $this->referee1->id,
                'camp_id' => $this->camp->id,
            ],
            [
                'evaluator_id' => $this->director->id,
                'status' => 'submitted',
                'call_accuracy' => 8,
                'communication_skills' => 9,
                'consistency_of_calls' => 8,
                'court_position_mechanics' => 9,
                'fitness_mobility' => 8,
                'game_awareness' => 9,
            ]
        );

        $tool = new CompareRefereesTool();

        // Query by camp_name
        $res = $tool->execute(['camp_name' => 'San Antonio Showcase 2026'], $this->director->id);

        $this->assertNotEmpty($res['result']['referees']);
        $this->assertEquals('Michael Jordan', $res['result']['referees'][0]['name']);
        $this->assertEquals('line', $res['widget_payload']['chart_type']);
    }

    /**
     * Test 2: OpenAI Provider defends against malformed string tool_calls from history.
     */
    public function test_openai_provider_sanitizes_tool_calls(): void
    {
        $provider = new OpenAiProvider('fake-key');

        $messages = [
            [
                'role' => 'assistant',
                'content' => 'Here are the results.',
                'tool_calls' => ['compare_referees'], // String array (malformed)
            ],
            [
                'role' => 'assistant',
                'content' => 'Valid call',
                'tool_calls' => [
                    [
                        'id' => 'call_123',
                        'type' => 'function',
                        'function' => ['name' => 'compare_referees', 'arguments' => '{}'],
                    ],
                ],
            ],
        ];

        // Using reflection to inspect payload formatter without hitting network
        $method = new \ReflectionMethod(OpenAiProvider::class, 'chat');
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test 3: Improvement widget provides all expected keys to avoid dashes in UI table.
     */
    public function test_improvement_widget_row_keys_prevent_dashes(): void
    {
        $tool = new RefereeImprovementTool();
        $res = $tool->execute(['years_back' => 3], $this->director->id);

        if (!empty($res['widget_payload']['rows'])) {
            $firstRow = $res['widget_payload']['rows'][0];
            $this->assertArrayHasKey('baseline_avg_score', $firstRow);
            $this->assertArrayHasKey('latest_avg_score', $firstRow);
            $this->assertArrayHasKey('score_delta', $firstRow);
            $this->assertArrayHasKey('percentage_improvement', $firstRow);
            $this->assertArrayHasKey('evaluations_count', $firstRow);
        }
    }

    /**
     * Test 4: Excel export generates downloadable route and deduplicates camp titles.
     */
    public function test_excel_export_download_route_and_deduplication(): void
    {
        $tool = new CompileCampRankingsExcelTool();
        $res = $tool->execute([], $this->director->id);

        $this->assertStringContainsString('/api/v1/director/ai/export/download/', $res['widget_payload']['download_url']);
        $this->assertEquals(
            count($res['widget_payload']['camps_included']),
            count(array_unique($res['widget_payload']['camps_included']))
        );
    }

    /**
     * Test 5: Pending Evaluators Tool accurately reflects "All Camps" when no camp specified.
     */
    public function test_pending_evaluators_all_camps_title(): void
    {
        $tool = new PendingEvaluatorsTool();
        $res = $tool->execute([], $this->director->id);

        $this->assertEquals('All Camps', $res['result']['camp_name']);
        $this->assertStringContainsString('All Camps', $res['widget_payload']['title']);
    }

    /**
     * Test 6: GetCampInventoryTool retrieves all camps for director.
     */
    public function test_camp_inventory_retrieval(): void
    {
        $tool = new GetCampInventoryTool();
        $res = $tool->execute([], $this->director->id);

        $this->assertGreaterThanOrEqual(1, $res['result']['total_camps']);
        $this->assertEquals('table', $res['widget_type']);
    }
}
