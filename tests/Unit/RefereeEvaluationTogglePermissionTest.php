<?php

namespace Tests\Unit;

use App\Models\CampRefereeJearsyNumber;
use App\Models\RefereeEvaluation;
use App\Models\SportsType;
use App\Models\User;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Tests\TestCase;

class RefereeEvaluationTogglePermissionTest extends TestCase
{
    protected User $director;
    protected User $otherDirector;
    protected User $evaluator;
    protected User $referee;
    protected Camp $camp;
    protected SportsType $sport;
    protected RefereeEvaluation $evaluation;
    protected CampRefereeCheckin $checkin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->director = User::firstOrCreate(
            ['email' => 'toggle_dir@whistleworks.org'],
            [
                'first_name' => 'Doug',
                'last_name' => 'Director',
                'username' => 'dougdirector_tog',
                'slug' => 'doug-director-tog',
                'password' => bcrypt('password123'),
                'role' => 'director',
            ]
        );

        $this->otherDirector = User::firstOrCreate(
            ['email' => 'toggle_otherdir@whistleworks.org'],
            [
                'first_name' => 'Dan',
                'last_name' => 'OtherDirector',
                'username' => 'danother_dir_tog',
                'slug' => 'dan-otherdir-tog',
                'password' => bcrypt('password123'),
                'role' => 'director',
            ]
        );

        $this->evaluator = User::firstOrCreate(
            ['email' => 'toggle_eval@whistleworks.org'],
            [
                'first_name' => 'Evan',
                'last_name' => 'Evaluator',
                'username' => 'evaneval_tog',
                'slug' => 'evan-eval-tog',
                'password' => bcrypt('password123'),
                'role' => 'evaluator',
            ]
        );

        $this->referee = User::firstOrCreate(
            ['email' => 'toggle_ref@whistleworks.org'],
            [
                'first_name' => 'Ron',
                'last_name' => 'Referee',
                'username' => 'ronref_tog',
                'slug' => 'ron-ref-tog',
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
            ['sports_name' => 'Basketball Toggle Test'],
            ['sports_fee' => 20, 'status' => 'active']
        );

        $this->camp = Camp::firstOrCreate(
            ['camp_name' => 'Toggle Permission Test Camp'],
            [
                'director_id' => $this->director->id,
                'sports_type_id' => $this->sport->id,
                'location' => 'Main Arena',
                'address' => '100 Stadium Way',
                'price' => 100,
                'status' => 'active',
                'publish_evaluations_for_referees' => true,
            ]
        );

        // Ensure camp settings
        $this->camp->publish_evaluations_for_referees = true;
        $this->camp->save();

        // Create or update referee checkin
        $this->checkin = CampRefereeCheckin::updateOrCreate(
            [
                'camp_id' => $this->camp->id,
                'referee_id' => $this->referee->id,
            ],
            [
                'registration_status' => 'checked_in',
                'checked_in_at' => now(),
                'can_view_evaluations' => true,
            ]
        );

        // Clean & create sample evaluation
        RefereeEvaluation::where('camp_id', $this->camp->id)->delete();
        CampRefereeJearsyNumber::where('camp_id', $this->camp->id)->delete();

        CampRefereeJearsyNumber::create([
            'camp_id' => $this->camp->id,
            'referee_id' => $this->referee->id,
            'jersey_number' => '42',
        ]);

        $this->evaluation = RefereeEvaluation::create([
            'camp_id' => $this->camp->id,
            'evaluator_id' => $this->evaluator->id,
            'referee_id' => $this->referee->id,
            'call_accuracy' => 9,
            'communication_skills' => 8,
            'consistency_of_calls' => 9,
            'court_position_mechanics' => 9,
            'fitness_mobility' => 8,
            'game_awareness' => 9,
            'referee_feedback' => 'Solid whistle control and good positioning.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->camp)) {
            RefereeEvaluation::where('camp_id', $this->camp->id)->delete();
            CampRefereeCheckin::where('camp_id', $this->camp->id)->delete();
            CampRefereeJearsyNumber::where('camp_id', $this->camp->id)->delete();
        }
        parent::tearDown();
    }

    public function test_director_can_toggle_referee_permission_off_and_referee_cannot_view_details(): void
    {
        // 1. Referee can initially view evaluation details
        $initialResponse = $this->actingAs($this->referee, 'api')
            ->getJson('/api/referee/my-evaluations/details/' . $this->evaluation->id);

        $initialResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->evaluation->id);

        // 2. Director toggles permission OFF for this referee
        $toggleResponse = $this->actingAs($this->director, 'api')
            ->putJson("/api/camp/{$this->camp->id}/ranking-settings/referee/{$this->referee->id}/toggle-permission", [
                'can_view_evaluations' => false,
            ]);

        $toggleResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.referee_id', $this->referee->id)
            ->assertJsonPath('data.can_view_evaluations', false);

        $this->assertDatabaseHas('camp_referee_checkins', [
            'camp_id' => $this->camp->id,
            'referee_id' => $this->referee->id,
            'can_view_evaluations' => false,
        ]);

        // 3. Referee tries to view evaluation details -> forbidden (403)
        $blockedResponse = $this->actingAs($this->referee, 'api')
            ->getJson('/api/referee/my-evaluations/details/' . $this->evaluation->id);

        $blockedResponse->assertStatus(403)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'You do not have permission to view evaluation details for this camp. Contact the director.');

        // 4. Director toggles permission back ON
        $toggleOnResponse = $this->actingAs($this->director, 'api')
            ->putJson("/api/camp/{$this->camp->id}/ranking-settings/referee/{$this->referee->id}/toggle-permission", [
                'can_view_evaluations' => true,
            ]);

        $toggleOnResponse->assertStatus(200)
            ->assertJsonPath('data.can_view_evaluations', true);

        // 5. Referee can view evaluation details again
        $allowedResponse = $this->actingAs($this->referee, 'api')
            ->getJson('/api/referee/my-evaluations/details/' . $this->evaluation->id);

        $allowedResponse->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_camp_level_ranking_settings_can_toggle_evaluations_visibility(): void
    {
        // 1. Director updates camp ranking settings with publish_evaluations_for_referees = false
        $updateResponse = $this->actingAs($this->director, 'api')
            ->putJson("/api/camp/{$this->camp->id}/ranking-settings", [
                'publish_evaluations_for_referees' => false,
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.publish_evaluations_for_referees', false);

        // Checkin record is updated as well
        $this->assertDatabaseHas('camp_referee_checkins', [
            'camp_id' => $this->camp->id,
            'referee_id' => $this->referee->id,
            'can_view_evaluations' => false,
        ]);

        // 2. Referee is blocked from viewing details
        $response = $this->actingAs($this->referee, 'api')
            ->getJson('/api/referee/my-evaluations/details/' . $this->evaluation->id);

        $response->assertStatus(403)
            ->assertJsonPath('status', false);

        // 3. Director turns it back on
        $this->actingAs($this->director, 'api')
            ->putJson("/api/camp/{$this->camp->id}/ranking-settings", [
                'publish_evaluations_for_referees' => true,
            ]);

        $allowedResponse = $this->actingAs($this->referee, 'api')
            ->getJson('/api/referee/my-evaluations/details/' . $this->evaluation->id);

        $allowedResponse->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_other_director_cannot_toggle_referee_permission(): void
    {
        $response = $this->actingAs($this->otherDirector, 'api')
            ->putJson("/api/camp/{$this->camp->id}/ranking-settings/referee/{$this->referee->id}/toggle-permission", [
                'can_view_evaluations' => false,
            ]);

        $response->assertStatus(403);
    }

    public function test_toggle_referee_permission_validation_fails_on_missing_boolean(): void
    {
        $response = $this->actingAs($this->director, 'api')
            ->putJson("/api/camp/{$this->camp->id}/ranking-settings/referee/{$this->referee->id}/toggle-permission", []);

        $response->assertStatus(422)
            ->assertJsonPath('status', false);
    }
}
