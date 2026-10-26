<?php

namespace Tests\Unit;

use App\Models\CampEvaluatorRegistration;
use App\Models\EvaluatorRefereeNote;
use App\Models\SportsType;
use App\Models\User;
use App\Services\Api\Evaluator\EvaluatorRefereeNoteService;
use Carbon\Carbon;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Modules\Director\Models\GameSlot;
use Modules\Director\Models\GameSlotAssignment;
use Modules\Director\Models\Schedule;
use Modules\Director\Models\ScheduleLocation;
use Tests\TestCase;

class EvaluatorRefereeNoteTest extends TestCase
{
    protected User $director;

    protected User $evaluator;

    protected User $otherEvaluator;

    protected User $unapprovedEvaluator;

    protected User $referee;

    protected Camp $camp;

    protected GameSlot $slot1;

    protected GameSlot $slot2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->director = User::firstOrCreate(
            ['email' => 'eval_note_dir@whistleworks.org'],
            [
                'first_name' => 'Dan',
                'last_name' => 'Director',
                'username' => 'dandirectornote',
                'slug' => 'dan-director-note',
                'password' => bcrypt('password123'),
                'role' => 'director',
            ]
        );

        $this->evaluator = User::firstOrCreate(
            ['email' => 'eval_note_eval1@whistleworks.org'],
            [
                'first_name' => 'Eric',
                'last_name' => 'Evaluator',
                'username' => 'ericevaluatornote',
                'slug' => 'eric-evaluator-note',
                'password' => bcrypt('password123'),
                'role' => 'evaluator',
            ]
        );

        $this->otherEvaluator = User::firstOrCreate(
            ['email' => 'eval_note_eval2@whistleworks.org'],
            [
                'first_name' => 'Other',
                'last_name' => 'Evaluator',
                'username' => 'otherevaluatornote',
                'slug' => 'other-evaluator-note',
                'password' => bcrypt('password123'),
                'role' => 'evaluator',
            ]
        );

        $this->unapprovedEvaluator = User::firstOrCreate(
            ['email' => 'eval_note_unapproved@whistleworks.org'],
            [
                'first_name' => 'Unapproved',
                'last_name' => 'Evaluator',
                'username' => 'unapprovedevaluatornote',
                'slug' => 'unapproved-evaluator-note',
                'password' => bcrypt('password123'),
                'role' => 'evaluator',
            ]
        );

        $this->referee = User::firstOrCreate(
            ['email' => 'eval_note_ref@whistleworks.org'],
            [
                'first_name' => 'Ryan',
                'last_name' => 'Referee',
                'username' => 'ryanrefereenote',
                'slug' => 'ryan-referee-note',
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
        if (! $this->otherEvaluator->hasRole('evaluator')) {
            $this->otherEvaluator->assignRole('evaluator');
        }
        if (! $this->unapprovedEvaluator->hasRole('evaluator')) {
            $this->unapprovedEvaluator->assignRole('evaluator');
        }
        if (! $this->referee->hasRole('referee')) {
            $this->referee->assignRole('referee');
        }

        $sport = SportsType::firstOrCreate(
            ['sports_name' => 'Basketball'],
            ['sports_fee' => 10, 'status' => 'active']
        );

        $this->camp = Camp::firstOrCreate(
            ['camp_name' => 'Evaluator Note Test Camp'],
            [
                'director_id' => $this->director->id,
                'sports_type_id' => $sport->id,
                'sports_type_name' => $sport->sports_name,
                'location' => 'Austin, TX',
                'price' => 120.00,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(2)->toDateString(),
                'status' => 'active',
            ]
        );

        // Approve evaluator 1 and otherEvaluator for this camp
        CampEvaluatorRegistration::updateOrCreate(
            ['camp_id' => $this->camp->id, 'evaluator_id' => $this->evaluator->id],
            ['status' => 'approved', 'approved_at' => now()]
        );
        CampEvaluatorRegistration::updateOrCreate(
            ['camp_id' => $this->camp->id, 'evaluator_id' => $this->otherEvaluator->id],
            ['status' => 'approved', 'approved_at' => now()]
        );

        // Checkin referee
        CampRefereeCheckin::firstOrCreate(
            ['camp_id' => $this->camp->id, 'referee_id' => $this->referee->id],
            ['registration_status' => 'checked_in']
        );

        // Create Schedule and 2 game slots
        $schedule = Schedule::firstOrCreate(
            ['camp_id' => $this->camp->id],
            [
                'game_duration' => 60,
                'break_duration' => 10,
                'max_referees_per_slot' => 3,
                'status' => 'draft',
                'mode' => 'individual',
            ]
        );

        $location = ScheduleLocation::firstOrCreate(
            ['schedule_id' => $schedule->id],
            ['location_name' => 'Court North', 'court_count' => 2]
        );

        $this->slot1 = GameSlot::firstOrCreate(
            ['schedule_id' => $schedule->id, 'schedule_location_id' => $location->id, 'court_number' => 1],
            [
                'game_date' => Carbon::now()->toDateString(),
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'court_name' => 'Court 1',
                'status' => 'available',
            ]
        );

        $this->slot2 = GameSlot::firstOrCreate(
            ['schedule_id' => $schedule->id, 'schedule_location_id' => $location->id, 'court_number' => 2],
            [
                'game_date' => Carbon::now()->toDateString(),
                'start_time' => '10:30:00',
                'end_time' => '11:30:00',
                'court_name' => 'Court 2',
                'status' => 'available',
            ]
        );

        // Assign referee to slot 1
        GameSlotAssignment::firstOrCreate(
            ['game_slot_id' => $this->slot1->id, 'assignable_id' => $this->referee->id],
            [
                'assignable_type' => User::class,
                'assignment_type' => 'individual',
                'position' => 'Referee 1',
            ]
        );

        // Clean previous notes
        EvaluatorRefereeNote::where('camp_id', $this->camp->id)->delete();
    }

    protected function tearDown(): void
    {
        if (isset($this->camp)) {
            EvaluatorRefereeNote::where('camp_id', $this->camp->id)->delete();
        }
        parent::tearDown();
    }

    public function test_evaluator_can_add_and_update_note(): void
    {
        $service = app(EvaluatorRefereeNoteService::class);

        // 1. Add note
        $res = $service->saveOrUpdateNote($this->evaluator, [
            'camp_id' => $this->camp->id,
            'referee_id' => $this->referee->id,
            'game_slot_id' => $this->slot1->id,
            'note' => 'Strong positioning on baseline, watch off-ball fouls.',
        ]);

        $this->assertTrue($res['success']);
        $this->assertEquals(200, $res['code']);
        $this->assertEquals('Strong positioning on baseline, watch off-ball fouls.', $res['data']['note']);
        $this->assertEquals($this->slot1->id, $res['data']['game_slot_id']);
        $this->assertEquals($this->referee->id, $res['data']['referee_id']);

        $noteId = $res['data']['id'];

        // 2. Update note using saveOrUpdateNote
        $resUpdate = $service->saveOrUpdateNote($this->evaluator, [
            'camp_id' => $this->camp->id,
            'referee_id' => $this->referee->id,
            'game_slot_id' => $this->slot1->id,
            'note' => 'Updated: excellent call accuracy in late game transition.',
        ]);

        $this->assertTrue($resUpdate['success']);
        $this->assertEquals($noteId, $resUpdate['data']['id']);
        $this->assertEquals('Updated: excellent call accuracy in late game transition.', $resUpdate['data']['note']);

        // Assert only 1 record exists for this slot & referee
        $count = EvaluatorRefereeNote::where('camp_id', $this->camp->id)
            ->where('evaluator_id', $this->evaluator->id)
            ->where('referee_id', $this->referee->id)
            ->where('game_slot_id', $this->slot1->id)
            ->count();
        $this->assertEquals(1, $count);

        // 3. Update note by ID
        $resEdit = $service->updateNoteById($this->evaluator, $noteId, 'Direct edit by ID.');
        $this->assertTrue($resEdit['success']);
        $this->assertEquals('Direct edit by ID.', $resEdit['data']['note']);
    }

    public function test_evaluator_can_delete_note(): void
    {
        $service = app(EvaluatorRefereeNoteService::class);

        $saved = $service->saveOrUpdateNote($this->evaluator, [
            'camp_id' => $this->camp->id,
            'referee_id' => $this->referee->id,
            'game_slot_id' => $this->slot1->id,
            'note' => 'To be deleted.',
        ]);

        $noteId = $saved['data']['id'];

        $delRes = $service->deleteNoteById($this->evaluator, $noteId);
        $this->assertTrue($delRes['success']);
        $this->assertDatabaseMissing('evaluator_referee_notes', ['id' => $noteId]);
    }

    public function test_evaluator_cannot_modify_other_evaluator_note(): void
    {
        $service = app(EvaluatorRefereeNoteService::class);

        $saved = $service->saveOrUpdateNote($this->evaluator, [
            'camp_id' => $this->camp->id,
            'referee_id' => $this->referee->id,
            'game_slot_id' => $this->slot1->id,
            'note' => 'Private to Evaluator 1.',
        ]);

        $noteId = $saved['data']['id'];

        // Evaluator 2 attempts to edit Evaluator 1's note
        $res = $service->updateNoteById($this->otherEvaluator, $noteId, 'Hacked note.');
        $this->assertFalse($res['success']);
        $this->assertEquals(403, $res['code']);

        // Evaluator 2 attempts to delete Evaluator 1's note
        $delRes = $service->deleteNoteById($this->otherEvaluator, $noteId);
        $this->assertFalse($delRes['success']);
        $this->assertEquals(403, $delRes['code']);
    }

    public function test_evaluator_not_approved_for_camp_cannot_add_note(): void
    {
        $service = app(EvaluatorRefereeNoteService::class);

        // unapprovedEvaluator is NOT approved for this camp
        $res = $service->saveOrUpdateNote($this->unapprovedEvaluator, [
            'camp_id' => $this->camp->id,
            'referee_id' => $this->referee->id,
            'game_slot_id' => $this->slot1->id,
            'note' => 'Unapproved attempt.',
        ]);

        $this->assertFalse($res['success']);
        $this->assertEquals(403, $res['code']);
    }

    public function test_evaluator_note_in_roster_camp_details_api(): void
    {
        $service = app(EvaluatorRefereeNoteService::class);

        // Evaluator 1 writes a note for Referee 1 in slot 1
        $service->saveOrUpdateNote($this->evaluator, [
            'camp_id' => $this->camp->id,
            'referee_id' => $this->referee->id,
            'game_slot_id' => $this->slot1->id,
            'note' => 'Evaluator 1 confidential note for slot 1.',
        ]);

        // 1. Evaluator 1 calls /api/roster/camp/details/{campId} -> CAN SEE THEIR NOTE
        $eval1Res = $this->actingAs($this->evaluator, 'api')
            ->getJson("/api/roster/camp/details/{$this->camp->id}");

        $eval1Res->assertStatus(200)->assertJsonPath('success', true);

        // Check in game_courts -> assignments
        $gameCourts = $eval1Res->json('data.camp.schedule.game_courts');
        $slot1Assignments = collect($gameCourts)->firstWhere('id', $this->slot1->id)['assignments'];
        $refAssignment = collect($slot1Assignments)->firstWhere('referee_id', $this->referee->id);

        $this->assertNotNull($refAssignment);
        $this->assertEquals('Evaluator 1 confidential note for slot 1.', $refAssignment['note']);
        $this->assertEquals('Evaluator 1 confidential note for slot 1.', $refAssignment['notes']);

        // Check in camp.referees
        $refereesList = $eval1Res->json('data.camp.referees');
        $refInList = collect($refereesList)->firstWhere('id', $this->referee->id);
        $this->assertNotNull($refInList);
        $this->assertEquals('Evaluator 1 confidential note for slot 1.', $refInList['note']);
        $this->assertEquals('Evaluator 1 confidential note for slot 1.', $refInList['notes']);

        // 2. Evaluator 2 calls /api/roster/camp/details/{campId} -> CANNOT SEE EVALUATOR 1's NOTE
        $eval2Res = $this->actingAs($this->otherEvaluator, 'api')
            ->getJson("/api/roster/camp/details/{$this->camp->id}");

        $eval2Res->assertStatus(200);
        $eval2Courts = $eval2Res->json('data.camp.schedule.game_courts');
        $eval2Slot1Assignments = collect($eval2Courts)->firstWhere('id', $this->slot1->id)['assignments'];
        $eval2RefAssignment = collect($eval2Slot1Assignments)->firstWhere('referee_id', $this->referee->id);

        $this->assertNull($eval2RefAssignment['note']);
        $this->assertNull($eval2RefAssignment['notes']);

        $eval2Referees = $eval2Res->json('data.camp.referees');
        $eval2RefInList = collect($eval2Referees)->firstWhere('id', $this->referee->id);
        $this->assertNull($eval2RefInList['note']);
        $this->assertNull($eval2RefInList['notes']);

        // 3. Director calls /api/roster/camp/details/{campId} -> CANNOT SEE EVALUATOR 1's NOTE
        $dirRes = $this->actingAs($this->director, 'api')
            ->getJson("/api/roster/camp/details/{$this->camp->id}");

        $dirRes->assertStatus(200);
        $dirCourts = $dirRes->json('data.camp.schedule.game_courts');
        $dirSlot1Assignments = collect($dirCourts)->firstWhere('id', $this->slot1->id)['assignments'];
        $dirRefAssignment = collect($dirSlot1Assignments)->firstWhere('referee_id', $this->referee->id);
        $this->assertNull($dirRefAssignment['note']);
        $this->assertNull($dirRefAssignment['notes']);

        // 4. Referee calls /api/roster/camp/details/{campId} -> CANNOT SEE EVALUATOR 1's NOTE
        $refRes = $this->actingAs($this->referee, 'api')
            ->getJson("/api/roster/camp/details/{$this->camp->id}");

        $refRes->assertStatus(200);
        $refCourts = $refRes->json('data.camp.schedule.game_courts');
        $refSlot1Assignments = collect($refCourts)->firstWhere('id', $this->slot1->id)['assignments'];
        $refereeAssignment = collect($refSlot1Assignments)->firstWhere('referee_id', $this->referee->id);
        $this->assertNull($refereeAssignment['note']);
        $this->assertNull($refereeAssignment['notes']);

        // 5. Evaluator 2 writes their OWN note for the referee
        $service->saveOrUpdateNote($this->otherEvaluator, [
            'camp_id' => $this->camp->id,
            'referee_id' => $this->referee->id,
            'game_slot_id' => $this->slot1->id,
            'note' => 'Evaluator 2 independent note.',
        ]);

        $eval2ResUpdated = $this->actingAs($this->otherEvaluator, 'api')
            ->getJson("/api/roster/camp/details/{$this->camp->id}");

        $eval2UpdatedCourts = $eval2ResUpdated->json('data.camp.schedule.game_courts');
        $eval2UpdatedAssignment = collect(collect($eval2UpdatedCourts)->firstWhere('id', $this->slot1->id)['assignments'])
            ->firstWhere('referee_id', $this->referee->id);

        $this->assertEquals('Evaluator 2 independent note.', $eval2UpdatedAssignment['note']);
        $this->assertEquals('Evaluator 2 independent note.', $eval2UpdatedAssignment['notes']);

        // Evaluator 1 STILL sees Evaluator 1's note (isolation preserved)
        $eval1ResCheck = $this->actingAs($this->evaluator, 'api')
            ->getJson("/api/roster/camp/details/{$this->camp->id}");
        $eval1CheckAssignment = collect(collect($eval1ResCheck->json('data.camp.schedule.game_courts'))->firstWhere('id', $this->slot1->id)['assignments'])
            ->firstWhere('referee_id', $this->referee->id);
        $this->assertEquals('Evaluator 1 confidential note for slot 1.', $eval1CheckAssignment['note']);
    }

    public function test_http_api_endpoints(): void
    {
        // 1. POST /api/referee/evaluation/note/store
        $response = $this->actingAs($this->evaluator, 'api')
            ->postJson('/api/referee/evaluation/note/store', [
                'camp_id' => $this->camp->id,
                'referee_id' => $this->referee->id,
                'game_slot_id' => $this->slot1->id,
                'note' => 'HTTP store test note.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.note', 'HTTP store test note.');

        $noteId = $response->json('data.id');

        // 2. POST /api/referee/evaluation/note/update/{id}
        $updateRes = $this->actingAs($this->evaluator, 'api')
            ->postJson('/api/referee/evaluation/note/update/'.$noteId, [
                'note' => 'Updated via HTTP update.',
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.note', 'Updated via HTTP update.');

        // 3. DELETE /api/referee/evaluation/note/destroy/{id}
        $delRes = $this->actingAs($this->evaluator, 'api')
            ->deleteJson('/api/referee/evaluation/note/destroy/'.$noteId);

        $delRes->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_court_level_evaluator_note_in_game_courts_is_isolated_and_conditional(): void
    {
        $service = app(EvaluatorRefereeNoteService::class);

        // Evaluator 1 writes a court-level note (referee_id is null)
        $service->saveOrUpdateNote($this->evaluator, [
            'camp_id' => $this->camp->id,
            'referee_id' => null,
            'game_slot_id' => $this->slot1->id,
            'note' => 'Court 1 evaluator note.',
        ]);

        // 1. Evaluator 1 sees the evaluator_note on the court
        $eval1Res = $this->actingAs($this->evaluator, 'api')
            ->getJson("/api/roster/camp/details/{$this->camp->id}");
        $eval1Res->assertStatus(200);

        $eval1Courts = $eval1Res->json('data.camp.schedule.game_courts');
        $eval1Slot1 = collect($eval1Courts)->firstWhere('id', $this->slot1->id);

        $this->assertArrayHasKey('evaluator_note', $eval1Slot1);
        $this->assertEquals('Court 1 evaluator note.', $eval1Slot1['evaluator_note']);
        $this->assertNotNull($eval1Slot1['evaluator_note_id']);

        // 2. Evaluator 2 CANNOT see Evaluator 1's court note (they see null)
        $eval2Res = $this->actingAs($this->otherEvaluator, 'api')
            ->getJson("/api/roster/camp/details/{$this->camp->id}");
        $eval2Res->assertStatus(200);

        $eval2Courts = $eval2Res->json('data.camp.schedule.game_courts');
        $eval2Slot1 = collect($eval2Courts)->firstWhere('id', $this->slot1->id);

        $this->assertArrayHasKey('evaluator_note', $eval2Slot1);
        $this->assertNull($eval2Slot1['evaluator_note']);

        // 3. Director does NOT see evaluator_note property at all
        $dirRes = $this->actingAs($this->director, 'api')
            ->getJson("/api/roster/camp/details/{$this->camp->id}");
        $dirRes->assertStatus(200);

        $dirCourts = $dirRes->json('data.camp.schedule.game_courts');
        $dirSlot1 = collect($dirCourts)->firstWhere('id', $this->slot1->id);

        $this->assertArrayNotHasKey('evaluator_note', $dirSlot1);
        $this->assertArrayNotHasKey('evaluator_note_id', $dirSlot1);

        // 4. Referee does NOT see evaluator_note property at all
        $refRes = $this->actingAs($this->referee, 'api')
            ->getJson("/api/roster/camp/details/{$this->camp->id}");
        $refRes->assertStatus(200);

        $refCourts = $refRes->json('data.camp.schedule.game_courts');
        $refSlot1 = collect($refCourts)->firstWhere('id', $this->slot1->id);

        $this->assertArrayNotHasKey('evaluator_note', $refSlot1);
        $this->assertArrayNotHasKey('evaluator_note_id', $refSlot1);
    }
}
