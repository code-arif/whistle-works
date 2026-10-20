<?php

namespace Tests\Unit;

use App\Models\CampRefereeJearsyNumber;
use App\Models\CampRefereeNote;
use App\Models\SportsType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Modules\Director\Models\GameSlot;
use Modules\Director\Models\GameSlotAssignment;
use Modules\Director\Models\Schedule;
use Modules\Director\Models\ScheduleLocation;
use Modules\Director\Services\CourtAssign\CourtAssignService;
use Modules\Director\Services\Crew\CrewService;
use Modules\Director\Services\Referee\RefereeService;
use Tests\TestCase;

class CampRefereeNoteTest extends TestCase
{
    protected User $director;
    protected User $referee;
    protected Camp $camp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->director = User::firstOrCreate(
            ['email' => 'note_director@whistleworks.org'],
            [
                'first_name' => 'John',
                'last_name' => 'Director',
                'username' => 'johndirectornote',
                'slug' => 'john-director-note',
                'password' => bcrypt('password123'),
                'role' => 'director',
            ]
        );

        $this->referee = User::firstOrCreate(
            ['email' => 'note_referee@whistleworks.org'],
            [
                'first_name' => 'Alice',
                'last_name' => 'Referee',
                'username' => 'alicerefereenote',
                'slug' => 'alice-referee-note',
                'password' => bcrypt('password123'),
                'role' => 'referee',
            ]
        );

        $sport = SportsType::firstOrCreate(
            ['sports_name' => 'Basketball'],
            ['sports_fee' => 10, 'status' => 'active']
        );

        $this->camp = Camp::firstOrCreate(
            ['camp_name' => 'Test Note Camp 2026'],
            [
                'director_id' => $this->director->id,
                'sports_type_id' => $sport->id,
                'sports_type_name' => $sport->sports_name,
                'location' => 'Dallas, TX',
                'price' => 100.00,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(3)->toDateString(),
                'status' => 'active',
            ]
        );

        CampRefereeCheckin::firstOrCreate(
            [
                'camp_id' => $this->camp->id,
                'referee_id' => $this->referee->id,
            ],
            [
                'registration_status' => 'checked_in',
                'registered_at' => now(),
                'checked_in_at' => now(),
                'checked_in_by' => 'director',
            ]
        );
    }

    protected function tearDown(): void
    {
        CampRefereeNote::where('camp_id', $this->camp->id)->delete();
        CampRefereeJearsyNumber::where('camp_id', $this->camp->id)->delete();
        parent::tearDown();
    }

    public function test_camp_referee_note_model_and_relationship(): void
    {
        $note = CampRefereeNote::updateOrCreate(
            [
                'camp_id' => $this->camp->id,
                'referee_id' => $this->referee->id,
            ],
            [
                'note' => 'Needs to improve transition defense calls.',
            ]
        );

        $this->assertDatabaseHas('camp_referee_notes', [
            'camp_id' => $this->camp->id,
            'referee_id' => $this->referee->id,
            'note' => 'Needs to improve transition defense calls.',
        ]);

        $this->assertEquals($this->camp->id, $note->camp->id);
        $this->assertEquals($this->referee->id, $note->referee->id);

        $freshCamp = Camp::with('refereeNotes')->find($this->camp->id);
        $this->assertTrue($freshCamp->refereeNotes->contains('id', $note->id));
    }

    public function test_referee_service_update_and_get_note(): void
    {
        $service = app(RefereeService::class);

        // 1. Add note
        $result = $service->updateRefereeNote(
            $this->director,
            $this->camp->id,
            $this->referee->id,
            'Solid footwork, excellent positioning'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['code']);
        $this->assertEquals('Solid footwork, excellent positioning', $result['data']['note']);
        $this->assertEquals('Solid footwork, excellent positioning', $result['data']['notes']);

        // 2. Get note
        $getResult = $service->getRefereeNote(
            $this->director,
            $this->camp->id,
            $this->referee->id
        );

        $this->assertTrue($getResult['success']);
        $this->assertEquals('Solid footwork, excellent positioning', $getResult['data']['note']);
        $this->assertEquals('Solid footwork, excellent positioning', $getResult['data']['notes']);

        // 3. Update note
        $updateResult = $service->updateRefereeNote(
            $this->director,
            $this->camp->id,
            $this->referee->id,
            'Updated: ready for high school varsity games'
        );

        $this->assertTrue($updateResult['success']);
        $this->assertEquals('Updated: ready for high school varsity games', $updateResult['data']['note']);

        // Verify only 1 record exists due to unique constraint
        $count = CampRefereeNote::where('camp_id', $this->camp->id)
            ->where('referee_id', $this->referee->id)
            ->count();
        $this->assertEquals(1, $count);
    }

    public function test_update_jersey_number_with_optional_note(): void
    {
        $service = app(RefereeService::class);

        $result = $service->updateRefereeJourcyNumber(
            $this->director,
            $this->camp->id,
            $this->referee->id,
            '77',
            'Jersey 77 requested by referee'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('77', $result['data']['jersey_assignment']['jersey_number']);
        $this->assertEquals('Jersey 77 requested by referee', $result['data']['note']);
        $this->assertEquals('Jersey 77 requested by referee', $result['data']['notes']);

        $savedNote = CampRefereeNote::where('camp_id', $this->camp->id)
            ->where('referee_id', $this->referee->id)
            ->value('note');
        $this->assertEquals('Jersey 77 requested by referee', $savedNote);
    }

    public function test_crew_service_endpoints_include_notes(): void
    {
        CampRefereeNote::updateOrCreate(
            ['camp_id' => $this->camp->id, 'referee_id' => $this->referee->id],
            ['note' => 'Strong crew chief material']
        );

        $crewService = app(CrewService::class);
        $request = new Request();

        // 1. Available referees for crew
        $availableRes = $crewService->getAvailableReferees($this->director, $this->camp->id, $request);
        $this->assertTrue($availableRes['success']);
        $refs = $availableRes['data']['referees'];
        $found = collect($refs)->firstWhere('id', $this->referee->id);
        $this->assertNotNull($found);
        $arrayData = $found->toArray($request);
        $this->assertEquals('Strong crew chief material', $arrayData['note']);
        $this->assertEquals('Strong crew chief material', $arrayData['notes']);

        // 2. All checked-in referees
        $checkedInRes = $crewService->getAllCheckedInReferees($this->director, $this->camp->id, $request);
        $this->assertTrue($checkedInRes['success']);
        $checkedRefs = $checkedInRes['data']['referees'];
        $foundChecked = collect($checkedRefs)->firstWhere('referee_id', $this->referee->id);
        $this->assertNotNull($foundChecked);
        $checkedArray = $foundChecked->toArray($request);
        $this->assertEquals('Strong crew chief material', $checkedArray['note']);
        $this->assertEquals('Strong crew chief material', $checkedArray['notes']);
    }

    public function test_court_assign_service_endpoints_include_notes(): void
    {
        CampRefereeNote::updateOrCreate(
            ['camp_id' => $this->camp->id, 'referee_id' => $this->referee->id],
            ['note' => 'Prefers baseline positioning']
        );

        $courtAssignService = app(CourtAssignService::class);

        // Available referees for camp
        $availableCamp = $courtAssignService->getAvailableReferees($this->director, $this->camp->id);
        $this->assertTrue($availableCamp['success']);
        $refereeData = collect($availableCamp['data']['referees'])->firstWhere('id', $this->referee->id);
        $this->assertNotNull($refereeData);
        $this->assertEquals('Prefers baseline positioning', $refereeData['note']);
        $this->assertEquals('Prefers baseline positioning', $refereeData['notes']);

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
            [
                'location_name' => 'Main Gym',
                'court_count' => 1,
            ]
        );

        $slot = GameSlot::firstOrCreate(
            [
                'schedule_id' => $schedule->id,
                'schedule_location_id' => $location->id,
                'court_number' => 1,
            ],
            [
                'game_date' => Carbon::now()->toDateString(),
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'court_name' => 'Court 1',
                'status' => 'available',
                'mode' => 'individual',
            ]
        );

        // Available referees for slot with time check
        $availableSlot = $courtAssignService->getAvailableRefereesForSlot($this->director, $slot->id);
        $this->assertTrue($availableSlot['success']);
        $slotRef = collect($availableSlot['data']['referees'])->firstWhere('id', $this->referee->id);
        $this->assertNotNull($slotRef);
        $this->assertEquals('Prefers baseline positioning', $slotRef['notes']);

        // Slot assignments
        $assignment = GameSlotAssignment::create([
            'game_slot_id' => $slot->id,
            'assignable_id' => $this->referee->id,
            'assignable_type' => User::class,
            'assignment_type' => 'individual',
            'position' => 'Referee 1',
        ]);

        $slotAssignments = $courtAssignService->getSlotAssignments($this->director, $slot->id);
        $this->assertTrue($slotAssignments['success']);
        $assignedItem = collect($slotAssignments['data']['assignments'])->firstWhere('referee.id', $this->referee->id);
        $this->assertNotNull($assignedItem);
        $this->assertEquals('Prefers baseline positioning', $assignedItem['referee']['note']);
        $this->assertEquals('Prefers baseline positioning', $assignedItem['referee']['notes']);

        // Assigned referees or crew
        $assignedRefOrCrew = $courtAssignService->getAssignedRefereesOrCrew($this->director, $slot->id);
        $this->assertTrue($assignedRefOrCrew['success']);
        $assignedRef = collect($assignedRefOrCrew['data']['referees'])->firstWhere('referee.referee_id', $this->referee->id);
        $this->assertNotNull($assignedRef);
        $this->assertEquals('Prefers baseline positioning', $assignedRef['referee']['note']);
        $this->assertEquals('Prefers baseline positioning', $assignedRef['referee']['notes']);

        $assignment->delete();
    }

    public function test_roster_service_camp_details_includes_notes(): void
    {
        CampRefereeNote::updateOrCreate(
            ['camp_id' => $this->camp->id, 'referee_id' => $this->referee->id],
            ['note' => 'Veteran NCAA official with strong game management']
        );

        $rosterService = app(\App\Services\Api\Roster\RosterService::class);
        $result = $rosterService->getCampDetails($this->camp->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['code']);

        // 1. Verify note in camp.referees
        $referees = $result['data']['camp']['referees'];
        $refereeData = collect($referees)->firstWhere('id', $this->referee->id);
        $this->assertNotNull($refereeData);
        $this->assertEquals('Veteran NCAA official with strong game management', $refereeData['note']);
        $this->assertEquals('Veteran NCAA official with strong game management', $refereeData['notes']);
    }
}
