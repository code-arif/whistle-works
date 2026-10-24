<?php

namespace Tests\Unit;

use App\Models\CampEvaluatorRegistration;
use App\Models\RecommendedLevel;
use App\Models\RefereeEvaluation;
use App\Models\SportsType;
use App\Models\SportsTypeLevel;
use App\Models\User;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Tests\TestCase;

class DirectorDeleteEvaluationTest extends TestCase
{
    protected User $director;

    protected User $otherDirector;

    protected User $evaluator;

    protected User $referee;

    protected Camp $camp;

    protected Camp $otherCamp;

    protected SportsType $sport;

    protected SportsTypeLevel $level;

    protected RefereeEvaluation $evaluation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->director = User::firstOrCreate(
            ['email' => 'del_eval_dir@whistleworks.org'],
            [
                'first_name' => 'Dean',
                'last_name' => 'Director',
                'username' => 'deandirector_del',
                'slug' => 'dean-director-del',
                'password' => bcrypt('password123'),
                'role' => 'director',
            ]
        );

        $this->otherDirector = User::firstOrCreate(
            ['email' => 'del_eval_other_dir@whistleworks.org'],
            [
                'first_name' => 'Other',
                'last_name' => 'Director',
                'username' => 'otherdirector_del',
                'slug' => 'other-director-del',
                'password' => bcrypt('password123'),
                'role' => 'director',
            ]
        );

        $this->evaluator = User::firstOrCreate(
            ['email' => 'del_eval_eval@whistleworks.org'],
            [
                'first_name' => 'Ed',
                'last_name' => 'Evaluator',
                'username' => 'edevaluator_del',
                'slug' => 'ed-evaluator-del',
                'password' => bcrypt('password123'),
                'role' => 'evaluator',
            ]
        );

        $this->referee = User::firstOrCreate(
            ['email' => 'del_eval_ref@whistleworks.org'],
            [
                'first_name' => 'Ray',
                'last_name' => 'Referee',
                'username' => 'rayref_del',
                'slug' => 'ray-ref-del',
                'password' => bcrypt('password123'),
                'role' => 'referee',
            ]
        );

        if (! $this->director->hasRole('director')) {
            $this->director->assignRole('director');
        }
        if (! $this->otherDirector->hasRole('director')) {
            $this->otherDirector->assignRole('director');
        }
        if (! $this->evaluator->hasRole('evaluator')) {
            $this->evaluator->assignRole('evaluator');
        }
        if (! $this->referee->hasRole('referee')) {
            $this->referee->assignRole('referee');
        }

        $this->sport = SportsType::firstOrCreate(
            ['sports_name' => 'Basketball Delete Test'],
            ['sports_fee' => 20, 'status' => 'active']
        );

        $this->level = SportsTypeLevel::firstOrCreate(
            ['sports_type_id' => $this->sport->id, 'name' => 'College Varsity Delete'],
            ['status' => 'active', 'sort_order' => 1]
        );

        $this->camp = Camp::firstOrCreate(
            ['camp_name' => 'Delete Test Camp'],
            [
                'director_id' => $this->director->id,
                'sports_type_id' => $this->sport->id,
                'location' => 'Austin, TX',
                'price' => 150,
                'status' => 'active',
            ]
        );

        $this->otherCamp = Camp::firstOrCreate(
            ['camp_name' => 'Other Director Camp'],
            [
                'director_id' => $this->otherDirector->id,
                'sports_type_id' => $this->sport->id,
                'location' => 'Dallas, TX',
                'price' => 150,
                'status' => 'active',
            ]
        );

        CampRefereeCheckin::firstOrCreate(
            ['camp_id' => $this->camp->id, 'referee_id' => $this->referee->id],
            ['registration_status' => 'checked_in']
        );

        CampEvaluatorRegistration::updateOrCreate(
            ['camp_id' => $this->camp->id, 'evaluator_id' => $this->evaluator->id],
            ['status' => 'approved', 'approved_at' => now()]
        );
    }

    protected function createEvaluation(): RefereeEvaluation
    {
        $evaluation = RefereeEvaluation::create([
            'camp_id' => $this->camp->id,
            'evaluator_id' => $this->evaluator->id,
            'referee_id' => $this->referee->id,
            'call_accuracy' => 8,
            'communication_skills' => 9,
            'consistency_of_calls' => 8,
            'court_position_mechanics' => 10,
            'fitness_mobility' => 8,
            'game_awareness' => 9,
            'referee_feedback' => 'Solid mechanics.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        RecommendedLevel::create([
            'evaluation_id' => $evaluation->id,
            'sports_type_level_id' => $this->level->id,
            'level' => 'College Varsity Delete',
        ]);

        return $evaluation;
    }

    public function test_camp_director_can_delete_evaluation_via_history_context_route(): void
    {
        $evaluation = $this->createEvaluation();

        $response = $this->actingAs($this->director, 'api')
            ->deleteJson("/api/camp/{$this->camp->id}/referee/{$this->referee->id}/evaluation/{$evaluation->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Evaluation deleted successfully.');

        $this->assertDatabaseMissing('referee_evaluations', ['id' => $evaluation->id]);
        $this->assertDatabaseMissing('recommended_levels', ['evaluation_id' => $evaluation->id]);
    }

    public function test_camp_director_can_delete_evaluation_via_history_alias_routes(): void
    {
        // 1. Via /camp/{campId}/referee/{refereeId}/history/{evaluationId}
        $evaluation1 = $this->createEvaluation();
        $response1 = $this->actingAs($this->director, 'api')
            ->deleteJson("/api/camp/{$this->camp->id}/referee/{$this->referee->id}/history/{$evaluation1->id}");

        $response1->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseMissing('referee_evaluations', ['id' => $evaluation1->id]);

        // 2. Via /camp/{campId}/evaluation/{evaluationId}
        $evaluation2 = $this->createEvaluation();
        $response2 = $this->actingAs($this->director, 'api')
            ->deleteJson("/api/camp/{$this->camp->id}/evaluation/{$evaluation2->id}");

        $response2->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseMissing('referee_evaluations', ['id' => $evaluation2->id]);

        // 3. Via /referee/evaluation/destroy/{id}
        $evaluation3 = $this->createEvaluation();
        $response3 = $this->actingAs($this->director, 'api')
            ->deleteJson("/api/referee/evaluation/destroy/{$evaluation3->id}");

        $response3->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseMissing('referee_evaluations', ['id' => $evaluation3->id]);
    }

    public function test_evaluator_cannot_delete_evaluation(): void
    {
        $evaluation = $this->createEvaluation();

        $response = $this->actingAs($this->evaluator, 'api')
            ->deleteJson("/api/camp/{$this->camp->id}/referee/{$this->referee->id}/evaluation/{$evaluation->id}");

        $response->assertStatus(403)
            ->assertJsonPath('status', false);

        $this->assertDatabaseHas('referee_evaluations', ['id' => $evaluation->id]);
    }

    public function test_referee_cannot_delete_evaluation(): void
    {
        $evaluation = $this->createEvaluation();

        $response = $this->actingAs($this->referee, 'api')
            ->deleteJson("/api/camp/{$this->camp->id}/referee/{$this->referee->id}/evaluation/{$evaluation->id}");

        $response->assertStatus(403)
            ->assertJsonPath('status', false);

        $this->assertDatabaseHas('referee_evaluations', ['id' => $evaluation->id]);
    }

    public function test_other_camp_director_cannot_delete_evaluation(): void
    {
        $evaluation = $this->createEvaluation();

        $response = $this->actingAs($this->otherDirector, 'api')
            ->deleteJson("/api/camp/{$this->camp->id}/referee/{$this->referee->id}/evaluation/{$evaluation->id}");

        $response->assertStatus(403)
            ->assertJsonPath('status', false);

        $this->assertDatabaseHas('referee_evaluations', ['id' => $evaluation->id]);
    }

    public function test_delete_returns_404_for_mismatched_camp_or_referee(): void
    {
        $evaluation = $this->createEvaluation();

        // Wrong camp ID
        $resCamp = $this->actingAs($this->director, 'api')
            ->deleteJson("/api/camp/99999/referee/{$this->referee->id}/evaluation/{$evaluation->id}");
        $resCamp->assertStatus(404);

        // Wrong referee ID
        $resRef = $this->actingAs($this->director, 'api')
            ->deleteJson("/api/camp/{$this->camp->id}/referee/99999/evaluation/{$evaluation->id}");
        $resRef->assertStatus(404);
    }
}
