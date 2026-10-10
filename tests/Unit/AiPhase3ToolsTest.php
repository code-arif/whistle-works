<?php

namespace Tests\Unit;

use App\Models\CampEvaluatorRegistration;
use App\Models\CampRefereeJearsyNumber;
use App\Models\RefereeEvaluation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Modules\Director\app\Services\Ai\AiToolRegistry;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Tests\TestCase;

class AiPhase3ToolsTest extends TestCase
{
    protected AiToolRegistry $registry;
    protected User $referee1;
    protected User $referee2;
    protected User $evaluator;
    protected Camp $campA;
    protected Camp $campB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new AiToolRegistry();

        // Setup test users and camps
        $this->referee1 = User::firstOrCreate(
            ['email' => 'referee1_test@whistleworks.org'],
            [
                'first_name' => 'Michael',
                'last_name' => 'Jordan',
                'username' => 'mj_ref_test',
                'slug' => 'mj-ref-test',
                'address' => '123 Main St, Tulsa, OK 74103',
                'phone' => '918-555-0101',
            ]
        );

        $this->referee2 = User::firstOrCreate(
            ['email' => 'referee2_test@whistleworks.org'],
            [
                'first_name' => 'Kobe',
                'last_name' => 'Bryant',
                'username' => 'kb_ref_test',
                'slug' => 'kb-ref-test',
                'address' => '456 Oak Ave, Dallas, TX 75201',
                'phone' => '214-555-0102',
            ]
        );

        $this->evaluator = User::firstOrCreate(
            ['email' => 'evaluator_test@whistleworks.org'],
            [
                'first_name' => 'Phil',
                'last_name' => 'Jackson',
                'username' => 'pj_eval_test',
                'slug' => 'pj-eval-test',
                'address' => '789 Pine Rd, Chicago, IL 60601',
            ]
        );

        $sport = \App\Models\SportsType::firstOrCreate(
            ['sports_name' => 'Basketball'],
            ['sports_fee' => 10, 'status' => 'active']
        );

        $this->campA = Camp::firstOrCreate(
            ['camp_name' => 'Camp Alpha 2026'],
            [
                'sports_type_id' => $sport->id,
                'sports_type_name' => $sport->sports_name,
                'location' => 'Dallas, TX',
                'price' => 150.00,
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-05',
                'director_id' => $this->evaluator->id,
                'status' => 'active',
            ]
        );

        $this->campB = Camp::firstOrCreate(
            ['camp_name' => 'Camp Beta 2026'],
            [
                'sports_type_id' => $sport->id,
                'sports_type_name' => $sport->sports_name,
                'location' => 'Tulsa, OK',
                'price' => 200.00,
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-05',
                'director_id' => $this->evaluator->id,
                'status' => 'active',
            ]
        );
    }

    public function test_compare_referees_tool(): void
    {
        // Seed evaluation for referee 1
        RefereeEvaluation::create([
            'referee_id' => $this->referee1->id,
            'evaluator_id' => $this->evaluator->id,
            'camp_id' => $this->campA->id,
            'call_accuracy' => 9,
            'communication_skills' => 8,
            'consistency_of_calls' => 9,
            'court_position_mechanics' => 8,
            'fitness_mobility' => 9,
            'game_awareness' => 8,
            'average_score' => 8.50,
            'total_score' => 51.00,
            'status' => 'submitted',
        ]);

        // Seed evaluation for referee 2
        RefereeEvaluation::create([
            'referee_id' => $this->referee2->id,
            'evaluator_id' => $this->evaluator->id,
            'camp_id' => $this->campA->id,
            'call_accuracy' => 7,
            'communication_skills' => 7,
            'consistency_of_calls' => 8,
            'court_position_mechanics' => 7,
            'fitness_mobility' => 8,
            'game_awareness' => 7,
            'average_score' => 7.33,
            'total_score' => 44.00,
            'status' => 'submitted',
        ]);

        $res = $this->registry->executeTool('compare_referees', [
            'referee_names' => ['Michael Jordan', 'Kobe Bryant'],
        ], $this->evaluator->id);

        $this->assertEquals('chart', $res['widget_type']);
        $this->assertEquals('line', $res['widget_payload']['chart_type']);
        $this->assertCount(2, $res['widget_payload']['datasets']);
        $this->assertCount(6, $res['widget_payload']['labels']);
        $this->assertEquals('Michael Jordan', $res['result']['referees'][0]['name']);
    }

    public function test_referee_improvement_tool(): void
    {
        // Clean previous test evaluations for referee1
        RefereeEvaluation::where('referee_id', $this->referee1->id)->delete();

        // Add past baseline evaluation (2 years ago)
        $past = RefereeEvaluation::create([
            'referee_id' => $this->referee1->id,
            'evaluator_id' => $this->evaluator->id,
            'camp_id' => $this->campA->id,
            'average_score' => 6.00,
            'total_score' => 36.00,
            'status' => 'submitted',
        ]);
        $past->created_at = Carbon::now()->subYears(2);
        $past->saveQuietly();

        // Add recent evaluation (today)
        $recent = RefereeEvaluation::create([
            'referee_id' => $this->referee1->id,
            'evaluator_id' => $this->evaluator->id,
            'camp_id' => $this->campA->id,
            'average_score' => 9.00,
            'total_score' => 54.00,
            'status' => 'submitted',
        ]);
        $recent->created_at = Carbon::now();
        $recent->saveQuietly();

        $res = $this->registry->executeTool('get_referee_improvement_analytics', [
            'years_back' => 3,
        ], $this->evaluator->id);

        $this->assertEquals('table', $res['widget_type']);
        $this->assertNotEmpty($res['result']['rankings']);
        $this->assertGreaterThanOrEqual(0, $res['result']['rankings'][0]['score_delta']);
    }

    public function test_camp_referees_location_tool(): void
    {
        // Check in referee1 (Tulsa, OK) to Camp B
        CampRefereeCheckin::firstOrCreate([
            'camp_id' => $this->campB->id,
            'referee_id' => $this->referee1->id,
        ], [
            'registration_status' => 'checked_in',
        ]);

        CampRefereeJearsyNumber::firstOrCreate([
            'camp_id' => $this->campB->id,
            'referee_id' => $this->referee1->id,
        ], [
            'jersey_number' => '23',
        ]);

        $res = $this->registry->executeTool('get_camp_referees_by_location', [
            'camp_name' => 'Camp Beta 2026',
            'location_query' => 'Tulsa, OK',
        ], $this->evaluator->id);

        $this->assertEquals('table', $res['widget_type']);
        $this->assertGreaterThanOrEqual(1, $res['result']['count']);
        $this->assertEquals('Michael Jordan', $res['result']['referees'][0]['name']);
        $this->assertEquals('23', $res['result']['referees'][0]['jersey_number']);
    }

    public function test_pending_evaluators_tool(): void
    {
        $nonCompliantEvaluator = User::firstOrCreate(
            ['email' => 'lazy_evaluator@whistleworks.org'],
            ['first_name' => 'Lazy', 'last_name' => 'Evaluator', 'username' => 'lazy_eval', 'slug' => 'lazy-eval']
        );

        CampEvaluatorRegistration::firstOrCreate([
            'camp_id' => $this->campB->id,
            'evaluator_id' => $nonCompliantEvaluator->id,
        ], [
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $res = $this->registry->executeTool('get_pending_evaluators', [
            'camp_id' => $this->campB->id,
        ], $this->evaluator->id);

        $this->assertEquals('table', $res['widget_type']);
        $this->assertGreaterThanOrEqual(1, $res['result']['zero_submission_count']);
        $this->assertContains('Lazy Evaluator', array_column($res['result']['pending_evaluators'], 'name'));
    }

    public function test_referee_performance_timeline_tool(): void
    {
        $res = $this->registry->executeTool('get_referee_performance_timeline', [
            'referee_name' => 'Michael Jordan',
            'years_back' => 3,
        ], $this->evaluator->id);

        $this->assertEquals('chart', $res['widget_type']);
        $this->assertEquals('line', $res['widget_payload']['chart_type']);
        $this->assertNotEmpty($res['widget_payload']['labels']);
        $this->assertNotEmpty($res['widget_payload']['datasets']);
    }

    public function test_compile_camp_rankings_excel_tool(): void
    {
        $res = $this->registry->executeTool('compile_camp_rankings_excel', [
            'camp_names' => ['Camp Alpha 2026', 'Camp Beta 2026'],
        ], $this->evaluator->id);

        $this->assertEquals('excel_download', $res['widget_type']);
        $this->assertNotEmpty($res['result']['download_url']);
        $this->assertNotEmpty($res['result']['filename']);

        $filePath = storage_path('app/public/ai_exports/' . $res['result']['filename']);
        $this->assertTrue(File::exists($filePath));
        $this->assertGreaterThan(0, File::size($filePath));

        // Clean up test generated file
        if (File::exists($filePath)) {
            File::delete($filePath);
        }
    }
}
