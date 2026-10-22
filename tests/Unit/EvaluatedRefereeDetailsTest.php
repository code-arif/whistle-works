<?php

namespace Tests\Unit;

use App\Models\CampRefereeJearsyNumber;
use App\Models\RecommendedLevel;
use App\Models\RefereeEvaluation;
use App\Models\SportsType;
use App\Models\SportsTypeLevel;
use App\Models\User;
use App\Services\Api\Referee\EvaluatedRefereeService;
use Modules\Director\Models\Camp;
use Tests\TestCase;

class EvaluatedRefereeDetailsTest extends TestCase
{
    protected User $director;

    protected User $evaluator;

    protected User $referee;

    protected User $otherReferee;

    protected Camp $camp;

    protected SportsType $sport;

    protected SportsTypeLevel $level;

    protected RefereeEvaluation $evaluation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->director = User::firstOrCreate(
            ['email' => 'ref_det_dir@whistleworks.org'],
            [
                'first_name' => 'Dean',
                'last_name' => 'Director',
                'username' => 'deandirector_det',
                'slug' => 'dean-director-det',
                'password' => bcrypt('password123'),
                'role' => 'director',
            ]
        );

        $this->evaluator = User::firstOrCreate(
            ['email' => 'ref_det_eval@whistleworks.org'],
            [
                'first_name' => 'Ed',
                'last_name' => 'Evaluator',
                'username' => 'edevaluator_det',
                'slug' => 'ed-evaluator-det',
                'password' => bcrypt('password123'),
                'role' => 'evaluator',
            ]
        );

        $this->referee = User::firstOrCreate(
            ['email' => 'ref_det_ref@whistleworks.org'],
            [
                'first_name' => 'Ray',
                'last_name' => 'Referee',
                'username' => 'rayref_det',
                'slug' => 'ray-ref-det',
                'password' => bcrypt('password123'),
                'role' => 'referee',
            ]
        );

        $this->otherReferee = User::firstOrCreate(
            ['email' => 'ref_det_other@whistleworks.org'],
            [
                'first_name' => 'Oscar',
                'last_name' => 'Other',
                'username' => 'oscarother_det',
                'slug' => 'oscar-other-det',
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
        if (! $this->referee->hasRole('referee')) {
            $this->referee->assignRole('referee');
        }
        if (! $this->otherReferee->hasRole('referee')) {
            $this->otherReferee->assignRole('referee');
        }

        $this->sport = SportsType::firstOrCreate(
            ['sports_name' => 'Basketball Details Test'],
            ['sports_fee' => 20, 'status' => 'active']
        );

        $this->level = SportsTypeLevel::firstOrCreate(
            ['sports_type_id' => $this->sport->id, 'name' => 'College Varsity'],
            ['status' => 'active', 'sort_order' => 1]
        );

        $this->camp = Camp::firstOrCreate(
            ['camp_name' => 'Details Test Camp'],
            [
                'director_id' => $this->director->id,
                'sports_type_id' => $this->sport->id,
                'location' => 'Court Central',
                'address' => '500 Hoops Blvd',
                'price' => 120,
                'status' => 'active',
                'hide_evaluator_name_from_referees' => false,
            ]
        );

        // Clean previous test evaluations for this camp
        RefereeEvaluation::where('camp_id', $this->camp->id)->delete();
        CampRefereeJearsyNumber::where('camp_id', $this->camp->id)->delete();

        // Assign jersey number
        CampRefereeJearsyNumber::create([
            'camp_id' => $this->camp->id,
            'referee_id' => $this->referee->id,
            'jersey_number' => '77',
        ]);

        // Create sample evaluation (criteria scores are integer 1-10)
        $this->evaluation = RefereeEvaluation::create([
            'camp_id' => $this->camp->id,
            'evaluator_id' => $this->evaluator->id,
            'referee_id' => $this->referee->id,
            'call_accuracy' => 8,
            'communication_skills' => 9,
            'consistency_of_calls' => 8,
            'court_position_mechanics' => 10,
            'fitness_mobility' => 8,
            'game_awareness' => 9,
            'referee_feedback' => 'Excellent communication and sharp mechanics in half-court transition.',
            'private_comments' => 'High tier talent, monitor for upcoming invitationals.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        RecommendedLevel::create([
            'evaluation_id' => $this->evaluation->id,
            'sports_type_level_id' => $this->level->id,
            'level' => 'College Varsity',
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->camp)) {
            RefereeEvaluation::where('camp_id', $this->camp->id)->delete();
            CampRefereeJearsyNumber::where('camp_id', $this->camp->id)->delete();
        }
        parent::tearDown();
    }

    public function test_referee_can_view_own_submitted_evaluation_details(): void
    {
        $service = app(EvaluatedRefereeService::class);
        $result = $service->getRefereeEvaluationDetails($this->referee, $this->evaluation->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['code']);

        $data = $result['data'];
        $this->assertEquals($this->evaluation->id, $data['id']);
        $this->assertEquals('submitted', $data['status']);
        $this->assertEquals('77', $data['referee']['jersey_number']);
        $this->assertEquals('Ray Referee', $data['referee']['name']);
        $this->assertEquals('Ed Evaluator', $data['evaluator']['name']);
        $this->assertFalse($data['evaluator']['is_anonymous']);

        // Check scores
        $this->assertEquals(8.0, $data['performance_criteria']['call_accuracy']['score']);
        $this->assertEquals(10.0, $data['performance_criteria']['court_position_mechanics']['score']);
        $this->assertEquals(52.0, $data['scores']['total_score']);
        $this->assertEquals(8.67, $data['scores']['average_score']);
        $this->assertEquals('Good', $data['scores']['rating']);

        // Check feedback
        $this->assertEquals('Excellent communication and sharp mechanics in half-court transition.', $data['referee_feedback']);

        // Check recommended levels
        $this->assertEquals('College Varsity', $data['highest_recommended_level']);
        $this->assertCount(1, $data['recommended_levels']);

        // Also verify the HTTP route directly
        $response = $this->actingAs($this->referee, 'api')
            ->getJson('/api/referee/my-evaluations/details/'.$this->evaluation->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->evaluation->id)
            ->assertJsonPath('data.evaluator.name', 'Ed Evaluator');
    }

    public function test_evaluator_name_is_hidden_when_camp_setting_enabled(): void
    {
        $this->camp->hide_evaluator_name_from_referees = true;
        $this->camp->save();

        $service = app(EvaluatedRefereeService::class);
        $result = $service->getRefereeEvaluationDetails($this->referee, $this->evaluation->id);

        $this->assertTrue($result['success']);
        $this->assertEquals('Anonymous Evaluator', $result['data']['evaluator']['name']);
        $this->assertNull($result['data']['evaluator']['id']);
        $this->assertTrue($result['data']['evaluator']['is_anonymous']);

        // Reset
        $this->camp->hide_evaluator_name_from_referees = false;
        $this->camp->save();
    }

    public function test_referee_cannot_view_another_referees_evaluation(): void
    {
        $service = app(EvaluatedRefereeService::class);
        $result = $service->getRefereeEvaluationDetails($this->otherReferee, $this->evaluation->id);

        $this->assertFalse($result['success']);
        $this->assertEquals(403, $result['code']);
    }

    public function test_referee_cannot_view_draft_evaluation(): void
    {
        $draftEval = RefereeEvaluation::create([
            'camp_id' => $this->camp->id,
            'evaluator_id' => $this->evaluator->id,
            'referee_id' => $this->referee->id,
            'call_accuracy' => 6.0,
            'status' => 'draft',
        ]);

        $service = app(EvaluatedRefereeService::class);
        $result = $service->getRefereeEvaluationDetails($this->referee, $draftEval->id);

        $this->assertFalse($result['success']);
        $this->assertEquals(403, $result['code']);
    }

    public function test_non_existent_evaluation_returns_404(): void
    {
        $service = app(EvaluatedRefereeService::class);
        $result = $service->getRefereeEvaluationDetails($this->referee, 999999);

        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['code']);
    }
}
