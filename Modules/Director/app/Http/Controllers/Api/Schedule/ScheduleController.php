<?php

namespace Modules\Director\Http\Controllers\Api\Schedule;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Director\Models\{Camp, Schedule, ScheduleLocation, ScheduleTimeRange, GameSlot, RefereeAssignment, CampRefereeCheckin};
use Modules\Director\Http\Requests\{ScheduleCreateRequest, LocationAddRequest};

class ScheduleController extends Controller
{
    use ApiResponse;

    /**
     * Create schedule with time ranges and locations
     */
    public function createSchedule(ScheduleCreateRequest $request, $campId)
    {
        $user = auth('api')->user();

        // Verify camp ownership
        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        // Check if schedule already exists
        if ($camp->schedule()->exists()) {
            return $this->error('Schedule already exists for this camp.', null, 400);
        }

        DB::beginTransaction();
        try {
            // Create schedule
            $schedule = Schedule::create([
                'camp_id' => $camp->id,
                'game_duration' => $request->game_duration,
                'status' => 'draft'
            ]);

            // Create time ranges
            foreach ($request->time_ranges as $range) {
                ScheduleTimeRange::create([
                    'schedule_id' => $schedule->id,
                    'date' => $range['date'],
                    'start_time' => $range['start_time'],
                    'end_time' => $range['end_time']
                ]);
            }

            // Create locations with courts
            foreach ($request->locations as $location) {
                ScheduleLocation::create([
                    'schedule_id' => $schedule->id,
                    'location_name' => $location['location_name'],
                    'court_count' => $location['court_count']
                ]);
            }

            // Generate game slots
            $this->generateGameSlots($schedule);

            DB::commit();

            return $this->success(
                'Schedule created successfully.',
                $this->formatScheduleResponse($schedule),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create schedule: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Generate game slots based on time ranges and courts
     */
    private function generateGameSlots(Schedule $schedule)
    {
        $timeRanges = $schedule->timeRanges;
        $locations = $schedule->locations;
        $gameDuration = $schedule->game_duration;

        foreach ($timeRanges as $range) {
            $startTime = Carbon::parse($range->start_time);
            $endTime = Carbon::parse($range->end_time);

            foreach ($locations as $location) {
                for ($courtNum = 1; $courtNum <= $location->court_count; $courtNum++) {
                    $currentTime = $startTime->copy();

                    while ($currentTime->copy()->addMinutes($gameDuration)->lte($endTime)) {
                        GameSlot::create([
                            'schedule_id' => $schedule->id,
                            'schedule_location_id' => $location->id,
                            'game_date' => $range->date,
                            'start_time' => $currentTime->format('H:i:s'),
                            'end_time' => $currentTime->copy()->addMinutes($gameDuration)->format('H:i:s'),
                            'court_name' => $location->location_name . ' - Court ' . $courtNum,
                            'court_number' => $courtNum,
                            'status' => 'available'
                        ]);

                        $currentTime->addMinutes($gameDuration);
                    }
                }
            }
        }
    }

    /**
     * Get schedule details with game slots
     */
    public function getSchedule($campId)
    {
        $user = auth('api')->user();

        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $schedule = $camp->schedule()->with(['locations', 'timeRanges', 'gameSlots.refereeAssignments.referee'])->first();

        if (!$schedule) {
            return $this->error('Schedule not found.', null, 404);
        }

        return $this->success(
            'Schedule fetched successfully.',
            $this->formatScheduleResponse($schedule),
            200
        );
    }

    /**
     * Get game slots grouped by date and court
     */
    public function getGameSlots($campId, Request $request)
    {
        $user = auth('api')->user();

        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $schedule = $camp->schedule;
        if (!$schedule) {
            return $this->error('Schedule not found.', null, 404);
        }

        // Get date from request or first date
        $date = $request->date ?? $schedule->timeRanges()->orderBy('date')->first()->date;

        // Get game slots for specific date
        $gameSlots = GameSlot::where('schedule_id', $schedule->id)
            ->where('game_date', $date)
            ->with(['location', 'refereeAssignments.referee'])
            ->orderBy('start_time')
            ->orderBy('court_number')
            ->get();

        // Group by time and court
        $grouped = $gameSlots->groupBy('start_time')->map(function ($slots) {
            return $slots->groupBy('court_name');
        });

        // Get available dates
        $availableDates = $schedule->timeRanges()->pluck('date')->unique()->values();

        return $this->success(
            'Game slots fetched successfully.',
            [
                'current_date' => $date,
                'available_dates' => $availableDates,
                'game_slots' => $grouped,
                'locations' => $schedule->locations
            ],
            200
        );
    }

    /**
     * Publish schedule
     */
    public function publishSchedule($campId)
    {
        $user = auth('api')->user();

        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $schedule = $camp->schedule;
        if (!$schedule) {
            return $this->error('Schedule not found.', null, 404);
        }

        $schedule->update(['status' => 'published']);

        return $this->success('Schedule published successfully.', null, 200);
    }

    /**
     * Clear schedule (remove all assignments)
     */
    public function clearSchedule($campId)
    {
        $user = auth('api')->user();

        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $schedule = $camp->schedule;
        if (!$schedule) {
            return $this->error('Schedule not found.', null, 404);
        }

        DB::beginTransaction();
        try {
            // Remove all assignments
            RefereeAssignment::whereIn('game_slot_id', $schedule->gameSlots()->pluck('id'))
                ->delete();

            // Reset slot statuses
            $schedule->gameSlots()->update(['status' => 'available']);

            DB::commit();

            return $this->success('Schedule cleared successfully.', null, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to clear schedule: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete entire schedule
     */
    public function deleteSchedule($campId)
    {
        $user = auth('api')->user();

        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $schedule = $camp->schedule;
        if (!$schedule) {
            return $this->error('Schedule not found.', null, 404);
        }

        $schedule->delete();

        return $this->success('Schedule deleted successfully.', null, 200);
    }

    /**
     * Format schedule response
     */
    private function formatScheduleResponse($schedule)
    {
        return [
            'id' => $schedule->id,
            'camp_id' => $schedule->camp_id,
            'game_duration' => $schedule->game_duration,
            'status' => $schedule->status,
            'time_ranges' => $schedule->timeRanges,
            'locations' => $schedule->locations,
            'total_game_slots' => $schedule->gameSlots()->count(),
            'assigned_slots' => $schedule->gameSlots()->where('status', 'assigned')->count(),
            'created_at' => $schedule->created_at
        ];
    }
}
