<?php

namespace Tests\Unit;

use App\Models\CampRefereeJearsyNumber;
use App\Models\CampRefereeNote;
use App\Models\RecommendedLevel;
use App\Models\RefereeEvaluation;
use App\Models\SportsType;
use App\Models\SportsTypeLevel;
use App\Models\User;
use App\Services\Api\RefereeEvaluation\RefereeEvaluationExportService;
use App\Services\Api\RefereeEvaluation\RefereeEvaluationService;
use Modules\Director\Models\Camp;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class RefereeEvaluationExportTest extends TestCase
{
    protected User $director;

    protected User $evaluator;

    protected User $referee1;

    protected User $referee2;

    protected User $unauthorizedUser;

    protected Camp $camp;

    protected SportsType $sport;

    protected SportsTypeLevel $levelD1;

    protected SportsTypeLevel $levelD2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->director = User::firstOrCreate(
            ['email' => 'export_dir@whistleworks.org'],
            [
                'first_name' => 'Dave',
                'last_name' => 'Director',
                'username' => 'davedirector_exp',
                'slug' => 'dave-director-exp',
                'password' => bcrypt('password123'),
                'role' => 'director',
            ]
        );

        $this->evaluator = User::firstOrCreate(
            ['email' => 'export_eval@whistleworks.org'],
            [
                'first_name' => 'Evan',
                'last_name' => 'Evaluator',
                'username' => 'evanevaluator_exp',
                'slug' => 'evan-evaluator-exp',
                'password' => bcrypt('password123'),
                'role' => 'evaluator',
            ]
        );

        $this->referee1 = User::firstOrCreate(
            ['email' => 'export_ref1@whistleworks.org'],
            [
                'first_name' => 'Ricky',
                'last_name' => 'Referee',
                'username' => 'rickyref_exp',
                'slug' => 'ricky-ref-exp',
                'phone' => '555-0101',
                'address' => 'Tulsa, OK',
                'password' => bcrypt('password123'),
                'role' => 'referee',
            ]
        );

        $this->referee2 = User::firstOrCreate(
            ['email' => 'export_ref2@whistleworks.org'],
            [
                'first_name' => 'Sam',
                'last_name' => 'Official',
                'username' => 'samofficial_exp',
                'slug' => 'sam-official-exp',
                'phone' => '555-0102',
                'address' => 'Dallas, TX',
                'password' => bcrypt('password123'),
                'role' => 'referee',
            ]
        );

        $this->unauthorizedUser = User::firstOrCreate(
            ['email' => 'export_stranger@whistleworks.org'],
            [
                'first_name' => 'Stranger',
                'last_name' => 'User',
                'username' => 'stranger_exp',
                'slug' => 'stranger-exp',
                'password' => bcrypt('password123'),
                'role' => 'referee',
            ]
        );

        if (! $this->director->hasRole('director')) {
            $this->director->assignRole('director');
        }

        if (! $this->evaluator->hasRole('evaluator')) {
            $this->evaluator->assignRole('evaluator');
        }

        if (! $this->referee1->hasRole('referee')) {
            $this->referee1->assignRole('referee');
        }

        if (! $this->referee2->hasRole('referee')) {
            $this->referee2->assignRole('referee');
        }

        $this->sport = SportsType::firstOrCreate(
            ['sports_name' => 'Test Basketball'],
            ['sports_fee' => 15, 'status' => 'active']
        );

        $this->levelD1 = SportsTypeLevel::firstOrCreate(
            ['sports_type_id' => $this->sport->id, 'name' => 'NCAA Division 1'],
            ['status' => 'active', 'sort_order' => 1]
        );

        $this->levelD2 = SportsTypeLevel::firstOrCreate(
            ['sports_type_id' => $this->sport->id, 'name' => 'NCAA Division 2'],
            ['status' => 'active', 'sort_order' => 2]
        );

        $this->camp = Camp::firstOrCreate(
            ['camp_name' => 'Export Championship Camp'],
            [
                'director_id' => $this->director->id,
                'sports_type_id' => $this->sport->id,
                'location' => 'Memorial Arena',
                'address' => '100 Arena Way',
                'price' => 150,
                'status' => 'active',
                'publish_ranking_for_referees' => true,
            ]
        );

        // Clean previous test data for this camp
        RefereeEvaluation::where('camp_id', $this->camp->id)->delete();
        CampRefereeJearsyNumber::where('camp_id', $this->camp->id)->delete();
        CampRefereeNote::where('camp_id', $this->camp->id)->delete();

        // Assign jersey numbers
        CampRefereeJearsyNumber::create([
            'camp_id' => $this->camp->id,
            'referee_id' => $this->referee1->id,
            'jersey_number' => '42',
        ]);

        CampRefereeJearsyNumber::create([
            'camp_id' => $this->camp->id,
            'referee_id' => $this->referee2->id,
            'jersey_number' => '99',
        ]);

        // Assign note
        CampRefereeNote::create([
            'camp_id' => $this->camp->id,
            'referee_id' => $this->referee1->id,
            'note' => 'Top performing prospect for national tournament',
        ]);

        // Create evaluation for Referee 1 (high scores => avg 9.0)
        $eval1 = RefereeEvaluation::create([
            'camp_id' => $this->camp->id,
            'evaluator_id' => $this->evaluator->id,
            'referee_id' => $this->referee1->id,
            'call_accuracy' => 9.0,
            'communication_skills' => 9.0,
            'consistency_of_calls' => 9.0,
            'court_position_mechanics' => 9.0,
            'fitness_mobility' => 9.0,
            'game_awareness' => 9.0,
            'referee_feedback' => 'Exceptional court presence and mechanic consistency.',
            'private_comments' => 'Clear D1 college prospect, recommend for finals.',
            'status' => 'submitted',
        ]);

        RecommendedLevel::create([
            'evaluation_id' => $eval1->id,
            'sports_type_level_id' => $this->levelD1->id,
            'level' => 'NCAA Division 1',
        ]);

        // Create evaluation for Referee 2 (lower scores => avg 7.0)
        $eval2 = RefereeEvaluation::create([
            'camp_id' => $this->camp->id,
            'evaluator_id' => $this->evaluator->id,
            'referee_id' => $this->referee2->id,
            'call_accuracy' => 7.0,
            'communication_skills' => 7.0,
            'consistency_of_calls' => 7.0,
            'court_position_mechanics' => 7.0,
            'fitness_mobility' => 7.0,
            'game_awareness' => 7.0,
            'referee_feedback' => 'Solid foundation, needs work on transition spacing.',
            'private_comments' => 'Suitable for D2 or junior college assignments.',
            'status' => 'submitted',
        ]);

        RecommendedLevel::create([
            'evaluation_id' => $eval2->id,
            'sports_type_level_id' => $this->levelD2->id,
            'level' => 'NCAA Division 2',
        ]);
    }

    public function test_get_evaluations_by_camp_returns_expected_ranking_and_metadata(): void
    {
        $evalService = app(RefereeEvaluationService::class);
        $result = $evalService->getEvaluationsByCamp($this->director, $this->camp->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['code']);

        $evaluations = $result['data']['evaluations'];
        $this->assertCount(2, $evaluations);

        // Verification of ranking: Ricky Referee should be Rank 1, Sam Official Rank 2
        $first = $evaluations[0];
        $second = $evaluations[1];

        $this->assertEquals(1, $first['rank']);
        $this->assertEquals('Ricky Referee', $first['referee_name']);
        $this->assertEquals('42', $first['jersey_number']);
        $this->assertEquals('555-0101', $first['referee_phone']);
        $this->assertEquals(9.0, $first['averages']['overall']);
        $this->assertEquals('NCAA Division 1', $first['highest_recommended_level']);
        $this->assertContains('Exceptional court presence and mechanic consistency.', $first['comments']);
        $this->assertContains('Clear D1 college prospect, recommend for finals.', $first['private_comments']);
        $this->assertEquals('Top performing prospect for national tournament', $first['referee_note']);

        $this->assertEquals(2, $second['rank']);
        $this->assertEquals('Sam Official', $second['referee_name']);
        $this->assertEquals('99', $second['jersey_number']);
        $this->assertEquals('555-0102', $second['referee_phone']);
        $this->assertEquals(7.0, $second['averages']['overall']);
        $this->assertEquals('NCAA Division 2', $second['highest_recommended_level']);
    }

    public function test_export_evaluations_as_excel_returns_streamed_xlsx_response(): void
    {
        $exportService = app(RefereeEvaluationExportService::class);
        $response = $exportService->exportEvaluationsByCamp($this->director, $this->camp->id, 'xlsx');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment;', $contentDisposition);
        $this->assertStringContainsString('.xlsx', $contentDisposition);
    }

    public function test_export_evaluations_as_csv_returns_streamed_csv_response(): void
    {
        $exportService = app(RefereeEvaluationExportService::class);
        $response = $exportService->exportEvaluationsByCamp($this->director, $this->camp->id, 'csv');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment;', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);
    }

    public function test_unauthorized_user_cannot_export_camp_evaluations(): void
    {
        $exportService = app(RefereeEvaluationExportService::class);
        $result = $exportService->exportEvaluationsByCamp($this->unauthorizedUser, $this->camp->id, 'xlsx');

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(403, $result['code']);
    }

    public function test_export_empty_camp_evaluations_gracefully(): void
    {
        // Camp with no evaluations
        $emptyCamp = Camp::create([
            'camp_name' => 'Empty Test Camp',
            'director_id' => $this->director->id,
            'sports_type_id' => $this->sport->id,
            'location' => 'Empty Arena',
            'price' => 100,
            'status' => 'active',
        ]);

        $exportService = app(RefereeEvaluationExportService::class);
        $response = $exportService->exportEvaluationsByCamp($this->director, $emptyCamp->id, 'xlsx');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }
}
