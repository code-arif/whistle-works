<?php

namespace Tests\Unit;

use App\Models\SportsType;
use App\Models\User;
use Carbon\Carbon;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Modules\Director\Models\GameSlot;
use Modules\Director\Models\GameSlotAssignment;
use Modules\Director\Models\Schedule;
use Modules\Director\Models\ScheduleLocation;
use Tests\TestCase;

class RefereeAssignedSlotsFilterTest extends TestCase
{
    protected User $director;

    protected User $referee;

    protected Camp $camp;

    protected Schedule $schedule;

    protected ScheduleLocation $location;

    protected GameSlot $pastSlot;

    protected GameSlot $ongoingSlot;

    protected GameSlot $upcomingSlot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->director = User::firstOrCreate(
            ['email' => 'slot_filter_dir@whistleworks.org'],
            [
                'first_name' => 'Dan',
                'last_name' => 'Director',
                'username' => 'dandirector_slot',
                'slug' => 'dan-director-slot',
                'password' => bcrypt('password123'),
                'role' => 'director',
            ]
        );

        $this->referee = User::firstOrCreate(
            ['email' => 'slot_filter_ref@whistleworks.org'],
            [
                'first_name' => 'Ron',
                'last_name' => 'Referee',
                'username' => 'ronref_slot',
                'slug' => 'ron-ref-slot',
                'password' => bcrypt('password123'),
                'role' => 'referee',
            ]
        );

        if (! $this->director->hasRole('director')) {
            $this->director->assignRole('director');
        }
        if (! $this->referee->hasRole('referee')) {
            $this->referee->assignRole('referee');
        }

        $sport = SportsType::firstOrCreate(
            ['sports_name' => 'Basketball Slot Filter Test'],
            ['sports_fee' => 20, 'status' => 'active']
        );

        $this->camp = Camp::firstOrCreate(
            ['camp_name' => 'Slot Filter Camp'],
            [
                'director_id' => $this->director->id,
                'sports_type_id' => $sport->id,
                'location' => 'Dallas, TX',
                'start_date' => Carbon::now()->subDays(2)->toDateString(),
                'end_date' => Carbon::now()->addDays(2)->toDateString(),
                'price' => 100,
                'status' => 'active',
            ]
        );

        // Checkin referee to camp
        CampRefereeCheckin::firstOrCreate(
            ['camp_id' => $this->camp->id, 'referee_id' => $this->referee->id],
            ['checked_in_at' => now()]
        );

        // Schedule
        $this->schedule = Schedule::firstOrCreate(
            ['camp_id' => $this->camp->id],
            [
                'game_duration' => 60,
                'max_referees_per_slot' => 3,
                'mode' => 'individual',
                'status' => 'published',
            ]
        );
        $this->schedule->update(['status' => 'published']);

        // Schedule Location
        $this->location = ScheduleLocation::firstOrCreate(
            ['schedule_id' => $this->schedule->id, 'location_name' => 'Main Gym'],
            [
                'address' => '100 Main St',
                'latitude' => 32.7767,
                'longitude' => -96.7970,
            ]
        );

        // Clean previous test slots
        $oldSlotIds = GameSlot::where('schedule_id', $this->schedule->id)->pluck('id');
        GameSlotAssignment::whereIn('game_slot_id', $oldSlotIds)->delete();
        GameSlot::whereIn('id', $oldSlotIds)->delete();

        $now = Carbon::now();

        // 1. Past Slot (Yesterday)
        $this->pastSlot = GameSlot::create([
            'schedule_id' => $this->schedule->id,
            'schedule_location_id' => $this->location->id,
            'game_date' => $now->copy()->subDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'court_name' => 'Court 1',
            'court_number' => 1,
            'status' => 'assigned',
            'is_block' => false,
        ]);

        GameSlotAssignment::create([
            'game_slot_id' => $this->pastSlot->id,
            'assignable_id' => $this->referee->id,
            'assignable_type' => User::class,
            'assignment_type' => 'individual',
            'position' => 'Lead',
            'is_auto_assigned' => false,
            'assigned_at' => now(),
        ]);

        // 2. Ongoing Slot (Started 15 mins ago, ends 45 mins later)
        $this->ongoingSlot = GameSlot::create([
            'schedule_id' => $this->schedule->id,
            'schedule_location_id' => $this->location->id,
            'game_date' => $now->toDateString(),
            'start_time' => $now->copy()->subMinutes(15)->format('H:i:s'),
            'end_time' => $now->copy()->addMinutes(45)->format('H:i:s'),
            'court_name' => 'Court 2',
            'court_number' => 2,
            'status' => 'assigned',
            'is_block' => false,
        ]);

        GameSlotAssignment::create([
            'game_slot_id' => $this->ongoingSlot->id,
            'assignable_id' => $this->referee->id,
            'assignable_type' => User::class,
            'assignment_type' => 'individual',
            'position' => 'Trail',
            'is_auto_assigned' => false,
            'assigned_at' => now(),
        ]);

        // 3. Upcoming Slot (Tomorrow)
        $this->upcomingSlot = GameSlot::create([
            'schedule_id' => $this->schedule->id,
            'schedule_location_id' => $this->location->id,
            'game_date' => $now->copy()->addDay()->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'court_name' => 'Court 3',
            'court_number' => 3,
            'status' => 'assigned',
            'is_block' => false,
        ]);

        GameSlotAssignment::create([
            'game_slot_id' => $this->upcomingSlot->id,
            'assignable_id' => $this->referee->id,
            'assignable_type' => User::class,
            'assignment_type' => 'individual',
            'position' => 'Center',
            'is_auto_assigned' => false,
            'assigned_at' => now(),
        ]);
    }

    public function test_referee_can_get_all_assigned_slots_without_filter(): void
    {
        $response = $this->actingAs($this->referee, 'api')
            ->getJson("/api/camp/{$this->camp->id}/assigned-slots");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.active_filter', 'all')
            ->assertJsonPath('data.statistics.total_assigned_slots', 3)
            ->assertJsonPath('data.statistics.past_slots', 1)
            ->assertJsonPath('data.statistics.ongoing_slots', 1)
            ->assertJsonPath('data.statistics.upcoming_slots', 1)
            ->assertJsonCount(3, 'data.game_slots');
    }

    public function test_referee_can_filter_past_slots(): void
    {
        $response = $this->actingAs($this->referee, 'api')
            ->getJson("/api/camp/{$this->camp->id}/assigned-slots?filter=past");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.active_filter', 'past')
            ->assertJsonPath('data.statistics.total_assigned_slots', 3)
            ->assertJsonCount(1, 'data.game_slots')
            ->assertJsonPath('data.game_slots.0.game_slot_id', $this->pastSlot->id)
            ->assertJsonPath('data.game_slots.0.timing_status', 'past')
            ->assertJsonPath('data.game_slots.0.game_details.timing_status', 'past');
    }

    public function test_referee_can_filter_ongoing_slots(): void
    {
        $response = $this->actingAs($this->referee, 'api')
            ->getJson("/api/camp/{$this->camp->id}/assigned-slots?filter=ongoing");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.active_filter', 'ongoing')
            ->assertJsonPath('data.statistics.total_assigned_slots', 3)
            ->assertJsonCount(1, 'data.game_slots')
            ->assertJsonPath('data.game_slots.0.game_slot_id', $this->ongoingSlot->id)
            ->assertJsonPath('data.game_slots.0.timing_status', 'ongoing')
            ->assertJsonPath('data.game_slots.0.game_details.timing_status', 'ongoing');
    }

    public function test_referee_can_filter_upcoming_slots(): void
    {
        $response = $this->actingAs($this->referee, 'api')
            ->getJson("/api/camp/{$this->camp->id}/assigned-slots?filter=upcoming");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.active_filter', 'upcoming')
            ->assertJsonPath('data.statistics.total_assigned_slots', 3)
            ->assertJsonCount(1, 'data.game_slots')
            ->assertJsonPath('data.game_slots.0.game_slot_id', $this->upcomingSlot->id)
            ->assertJsonPath('data.game_slots.0.timing_status', 'upcoming')
            ->assertJsonPath('data.game_slots.0.game_details.timing_status', 'upcoming');
    }

    public function test_status_query_param_alias_works(): void
    {
        $response = $this->actingAs($this->referee, 'api')
            ->getJson("/api/camp/{$this->camp->id}/assigned-slots?status=ongoing");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.active_filter', 'ongoing')
            ->assertJsonCount(1, 'data.game_slots')
            ->assertJsonPath('data.game_slots.0.game_slot_id', $this->ongoingSlot->id);
    }

    public function test_completed_game_slot_is_filtered_as_past(): void
    {
        // Mark upcoming slot as completed
        $this->upcomingSlot->update(['status' => 'completed']);

        $response = $this->actingAs($this->referee, 'api')
            ->getJson("/api/camp/{$this->camp->id}/assigned-slots?filter=past");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.game_slots');
    }

    public function test_referee_prefix_route_works_identically(): void
    {
        $response = $this->actingAs($this->referee, 'api')
            ->getJson("/api/referee/camp/{$this->camp->id}/assigned-slots?filter=ongoing");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.active_filter', 'ongoing')
            ->assertJsonCount(1, 'data.game_slots')
            ->assertJsonPath('data.game_slots.0.game_slot_id', $this->ongoingSlot->id);
    }

    public function test_pagination_reflects_filtered_count(): void
    {
        $response = $this->actingAs($this->referee, 'api')
            ->getJson("/api/camp/{$this->camp->id}/assigned-slots?filter=ongoing&per_page=10&page=1");

        $response->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.pagination.last_page', 1)
            ->assertJsonPath('data.pagination.per_page', 10);
    }

    public function test_director_cannot_access_referee_assigned_slots(): void
    {
        $response = $this->actingAs($this->director, 'api')
            ->getJson("/api/camp/{$this->camp->id}/assigned-slots");

        $response->assertStatus(403);
    }
}
