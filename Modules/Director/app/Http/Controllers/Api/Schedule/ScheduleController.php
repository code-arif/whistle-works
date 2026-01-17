<?php

namespace Modules\Director\Http\Controllers\Api\Schedule;

use Exception;
use Carbon\Carbon;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Services\LocationService;
use Modules\Director\Models\Crew;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\Director\Models\GameSlotAssignment;
use Modules\Director\Http\Requests\{ScheduleCreateRequest, LocationAddRequest};
use Modules\Director\Models\{Camp, Schedule, ScheduleLocation, ScheduleTimeRange, GameSlot};

class ScheduleController extends Controller
{
    use ApiResponse;

    protected $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Get camp date range for schedule creation
     */
    public function getCampDateRange($campId)
    {
        $user = auth('api')->user();

        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->withCount('checkedInReferees')
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        // Parse dates in camp's timezone
        $campTimezone = $camp->timezone ?? 'UTC';
        $startDate = Carbon::parse($camp->start_date, $campTimezone)->startOfDay();
        $endDate = Carbon::parse($camp->end_date, $campTimezone)->startOfDay();

        $dates = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dates[] = [
                'date' => $currentDate->format('Y-m-d'),
                'day' => $currentDate->format('l'),
                'formatted' => $currentDate->format('M d, Y')
            ];
            $currentDate->addDay();
        }

        return $this->success(
            'Camp date range fetched successfully.',
            [
                'camp_id' => $camp->id,
                'camp_name' => $camp->camp_name,
                'timezone' => $campTimezone,
                'timezone_name' => $camp->timezone_display_name,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_days' => count($dates),
                'checked_in_referees_count' => $camp->checked_in_referees_count,
                'dates' => $dates
            ],
            200
        );
    }

    /**
     * Create schedule
     */
    public function createSchedule(ScheduleCreateRequest $request, $campId)
    {
        $user = auth('api')->user();

        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        if ($camp->schedule()->exists()) {
            return $this->error('Schedule already exists for this camp. Delete existing schedule first.', null, 400);
        }

        $campTimezone = $camp->timezone ?? 'UTC';

        // Convert to Y-m-d string format
        $campStartDate = Carbon::parse($camp->start_date)->format('Y-m-d');
        $campEndDate = Carbon::parse($camp->end_date)->format('Y-m-d');

        // Validate dates are within camp range
        foreach ($request->time_ranges as $range) {
            $rangeDate = $range['date']; // Already Y-m-d format from request

            // Simple string comparison for dates
            if ($rangeDate < $campStartDate || $rangeDate > $campEndDate) {
                return $this->error(
                    "Date {$rangeDate} is outside camp date range ({$campStartDate} to {$campEndDate}).",
                    null,
                    400
                );
            }
        }

        DB::beginTransaction();
        try {
            // Create schedule
            $schedule = Schedule::create([
                'camp_id' => $camp->id,
                'game_duration' => $request->game_duration,
                'max_referees_per_slot' => $request->max_referees_per_slot,
                'status' => 'draft'
            ]);

            // Create time ranges - store in camp timezone
            foreach ($request->time_ranges as $range) {
                // Validate time format
                if (
                    !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $range['start_time']) ||
                    !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $range['end_time'])
                ) {
                    DB::rollBack();
                    return $this->error('Invalid time format. Use HH:MM format (e.g., 09:00, 17:30).', null, 400);
                }

                // Compare times as strings
                if ($range['end_time'] <= $range['start_time']) {
                    DB::rollBack();
                    return $this->error('End time must be after start time.', null, 400);
                }

                ScheduleTimeRange::create([
                    'schedule_id' => $schedule->id,
                    'date' => $range['date'],
                    'start_time' => $range['start_time'] . ':00',
                    'end_time' => $range['end_time'] . ':00'
                ]);
            }

            // Create locations
            // $locationMap = [];
            // foreach ($request->locations as $location) {
            //     $scheduleLocation = ScheduleLocation::create([
            //         'schedule_id' => $schedule->id,
            //         'location_name' => $location['location_name'],
            //         'latitude' => $location['latitude'] ?? null,
            //         'longitude' => $location['longitude'] ?? null,
            //         'court_count' => $location['court_count']
            //     ]);

            //     $locationMap[] = $scheduleLocation;
            // }

            // Create locations with proper name from coordinates
            $locationMap = [];
            foreach ($request->locations as $index => $location) {
                // Determine location name
                $locationName = $location['location_name'] ?? null;
                $latitude = $location['latitude'] ?? null;
                $longitude = $location['longitude'] ?? null;

                // If lat/long provided, fetch accurate location name from Google
                if ($latitude && $longitude) {
                    // Validate coordinates
                    if (!$this->locationService->validateCoordinates($latitude, $longitude)) {
                        DB::rollBack();
                        return $this->error("Invalid coordinates for location #" . ($index + 1), null, 400);
                    }

                    // Fetch location data from Google
                    $locationResult = $this->locationService->getLocationFromCoordinates(
                        $latitude,
                        $longitude,
                        'detailed' // Options: 'short', 'detailed', 'address', 'full'
                    );

                    if ($locationResult['success']) {
                        // Use Google's location name if frontend name is too long or empty
                        if (empty($locationName) || strlen($locationName) > 100) {
                            $locationName = $locationResult['location_name'];
                        }

                        // Optionally: You can also use formatted_address
                        // $locationName = $locationResult['formatted_address'];
                    } else {
                        // If Google fetch fails, fallback to provided name or generic
                        if (empty($locationName)) {
                            $locationName = "Location " . ($index + 1);
                        }
                    }
                } else {
                    // No coordinates provided
                    if (empty($locationName)) {
                        DB::rollBack();
                        return $this->error(
                            "Location name or coordinates required for location #" . ($index + 1),
                            null,
                            400
                        );
                    }
                }

                $scheduleLocation = ScheduleLocation::create([
                    'schedule_id' => $schedule->id,
                    'location_name' => $locationName,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'court_count' => $location['court_count'] ?? 1
                ]);

                $locationMap[] = $scheduleLocation;
            }

            // Generate game slots
            $slotsGenerated = $this->generateGameSlots($schedule, $locationMap, $camp);

            DB::commit();

            return $this->success(
                'Schedule created successfully.',
                array_merge(
                    $this->formatScheduleResponse($schedule),
                    ['slots_generated' => $slotsGenerated]
                ),
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create schedule: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Generate game slots in camp timezone
     */
    private function generateGameSlots(Schedule $schedule, $locationMap, Camp $camp)
    {
        $timeRanges = $schedule->timeRanges;
        $gameDuration = $schedule->game_duration;
        $totalSlotsCreated = 0;
        $campTimezone = $camp->timezone ?? 'UTC';

        foreach ($timeRanges as $range) {
            $gameDate = $range->date;

            // Parse times in camp timezone
            $startTime = Carbon::parse($range->start_time, $campTimezone);
            $endTime = Carbon::parse($range->end_time, $campTimezone);
            $currentTime = $startTime->copy();

            // Generate time slots
            while ($currentTime->copy()->addMinutes($gameDuration)->lte($endTime)) {
                $slotStartTime = $currentTime->format('H:i:s');
                $slotEndTime = $currentTime->copy()->addMinutes($gameDuration)->format('H:i:s');

                // Create slots for each location with its court count
                foreach ($locationMap as $location) {
                    for ($courtNum = 1; $courtNum <= $location->court_count; $courtNum++) {
                        GameSlot::create([
                            'schedule_id' => $schedule->id,
                            'schedule_location_id' => $location->id,
                            'game_date' => $gameDate,
                            'start_time' => $slotStartTime,
                            'end_time' => $slotEndTime,
                            'court_name' => "Court {$courtNum}",
                            'court_number' => $courtNum,
                            'status' => 'available',
                            'is_block' => false
                        ]);

                        $totalSlotsCreated++;
                    }
                }

                $currentTime->addMinutes($gameDuration);
            }
        }

        return $totalSlotsCreated;
    }

    /**
     * Get schedule details
     */
    public function getSchedule($campId, Request $request)
    {
        $user = auth('api')->user();

        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $schedule = $camp->schedule()
            ->with(['locations', 'timeRanges', 'gameSlots'])
            ->first();

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
     * Get game slots grouped by date, time, and court
     */
    public function getGameSlots($campId, Request $request)
    {
        $user = auth('api')->user();

        $camp = Camp::where('director_id', $user->id)->find($campId);

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $schedule = $camp->schedule;
        if (!$schedule) {
            return $this->error('Schedule not found.', null, 404);
        }

        $campTimezone = $camp->timezone ?? 'UTC';

        // Get available dates
        $availableDates = $schedule->timeRanges()
            ->orderBy('date')
            ->get()
            ->map(function ($range) use ($campTimezone) {
                $date = Carbon::parse($range->date, $campTimezone);
                return [
                    'date' => $range->date,
                    'formatted' => $date->format('F d'),
                    'day' => $date->format('l')
                ];
            });

        // Get date from request or use first date
        $selectedDate = $request->date ?? $availableDates->first()['date'];

        // Get all available locations for this schedule
        $availableLocations = $schedule->locations()
            ->orderBy('location_name')
            ->get()
            ->map(function ($location) {
                return [
                    'location_id' => $location->id,
                    'location_name' => $location->location_name,
                    'court_count' => $location->court_count,
                ];
            });

        // Get selected location from request (optional)
        $selectedLocationId = $request->location_id ?? null;

        // Validate location_id if provided
        if ($selectedLocationId && !$availableLocations->contains('location_id', $selectedLocationId)) {
            return $this->error('Invalid location ID.', [
                'provided_location_id' => $selectedLocationId,
                'available_locations' => $availableLocations
            ], 400);
        }

        // Build query for game slots
        $gameSlotsQuery = GameSlot::where('schedule_id', $schedule->id)
            ->where('game_date', $selectedDate)
            ->with([
                'location',
                'slotAssignments.assignable' => function ($query) {
                    $query->when(function ($q) {
                        return $q->getModel() instanceof Crew;
                    }, function ($q) {
                        $q->with('members');
                    });
                }
            ]);

        // Apply location filter if provided
        if ($selectedLocationId) {
            $gameSlotsQuery->where('schedule_location_id', $selectedLocationId);
        }

        // Get filtered game slots
        $gameSlots = $gameSlotsQuery
            ->orderBy('start_time')
            ->orderBy('court_number')
            ->get();

        $scheduleFormat = [
            'schedule_id' => $schedule->id,
            'max_referees_per_slot' => $schedule->max_referees_per_slot,
            'status' => $schedule->status,
        ];

        // Group by time and format
        $timeSlots = $gameSlots->groupBy('start_time')->map(function ($slots, $time) use ($campTimezone) {
            $timeCarbon = Carbon::parse($time, $campTimezone);

            return [
                'time' => $timeCarbon->format('h:i A'),
                'time_24h' => $time,
                'courts' => $slots->map(function ($slot) use ($campTimezone) {
                    $startTime = Carbon::parse($slot->start_time, $campTimezone);
                    $endTime = Carbon::parse($slot->end_time, $campTimezone);

                    return [
                        'slot_id' => $slot->id,
                        'court_name' => $slot->court_name,
                        'court_number' => $slot->court_number,
                        'location' => $slot->location->location_name,
                        'location_id' => $slot->location->id,
                        'status' => $slot->status,
                        'is_blocked' => $slot->is_block,
                        'start_time' => $startTime->format('H:i'),
                        'end_time' => $endTime->format('H:i'),
                        'start_time_display' => $startTime->format('h:i A'),
                        'end_time_display' => $endTime->format('h:i A'),
                        'assignments_count' => $slot->slotAssignments->count(),
                        'assignments' => $slot->slotAssignments->map(function ($assignment) {
                            if ($assignment->assignment_type === 'crew') {
                                $crew = $assignment->assignable;
                                $members = $crew->members->map(function ($member) {
                                    return [
                                        'referee_id' => $member->id,
                                        'referee_name' => $member->first_name . ' ' . $member->last_name,
                                        'avatar' => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                                        'email' => $member->email,
                                    ];
                                });

                                return [
                                    'assignment_id' => $assignment->id,
                                    'type' => 'crew',
                                    'crew_id' => $crew->id,
                                    'crew_name' => $crew->name ?? 'Unknown',
                                    'member_count' => $crew->members->count(),
                                    'members' => $members
                                ];
                            } else {
                                return [
                                    'type' => 'individual',
                                    'assignment_id' => $assignment->id,
                                    'referee_id' => $assignment->assignable->id,
                                    'referee_name' => ($assignment->assignable->first_name ?? '') . ' ' . ($assignment->assignable->last_name ?? ''),
                                    'avatar' => $assignment->assignable->avatar
                                        ? asset($assignment->assignable->avatar)
                                        : asset('default/profile.jpg'),
                                ];
                            }
                        })
                    ];
                })->values()
            ];
        })->values();

        // Get unique court headers
        $courtHeaders = $gameSlots->unique(function ($slot) {
            return $slot->location->id . '-' . $slot->court_number;
        })
            ->sortBy('court_number')
            ->map(function ($slot) {
                return [
                    'location' => $slot->location->location_name,
                    'location_id' => $slot->location->id,
                    'court_name' => $slot->court_name,
                    'court_number' => $slot->court_number
                ];
            })
            ->values();

        // Get selected location details
        $selectedLocation = null;
        if ($selectedLocationId) {
            $selectedLocation = $availableLocations->firstWhere('location_id', $selectedLocationId);
        }

        return $this->success(
            'Game slots fetched successfully.',
            [
                'schedule' => $scheduleFormat,
                'camp_timezone' => $campTimezone,
                'camp_timezone_name' => $camp->timezone_display_name,
                'selected_date' => $selectedDate,
                'selected_date_formatted' => Carbon::parse($selectedDate, $campTimezone)->format('F d, Y'),
                'available_dates' => $availableDates,
                'available_locations' => $availableLocations,
                'selected_location' => $selectedLocation,
                'court_headers' => $courtHeaders,
                'time_slots' => $timeSlots,
                'total_slots' => $gameSlots->count(),
                'assigned_slots' => $gameSlots->where('status', 'assigned')->count()
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
     * Clear schedule (remove all assignments but keep slots)
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
            GameSlotAssignment::whereIn(
                'game_slot_id',
                $schedule->gameSlots()->pluck('id')
            )->delete();

            // Reset slot statuses
            $schedule->gameSlots()->update(['status' => 'available']);

            DB::commit();

            return $this->success('All assignments cleared successfully.', null, 200);
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
     * Format schedule response with camp timezone
     */
    private function formatScheduleResponse($schedule)
    {
        $camp = $schedule->camp;
        $campTimezone = $camp->timezone ?? 'UTC';

        return [
            'id' => $schedule->id,
            'camp_id' => $schedule->camp_id,
            'game_duration' => $schedule->game_duration,
            'status' => $schedule->status,
            'max_referees_per_slot' => $schedule->max_referees_per_slot,
            'camp_timezone' => $campTimezone,
            'camp_timezone_name' => $camp->timezone_display_name,
            'time_ranges' => $schedule->timeRanges->map(function ($range) use ($campTimezone) {
                $date = Carbon::parse($range->date, $campTimezone);
                $startTime = Carbon::parse($range->start_time, $campTimezone);
                $endTime = Carbon::parse($range->end_time, $campTimezone);

                return [
                    'date' => $range->date,
                    'formatted_date' => $date->format('M d, Y'),
                    'start_time' => $startTime->format('h:i A'),
                    'end_time' => $endTime->format('h:i A'),
                ];
            }),
            'locations' => $schedule->locations->map(function ($location) {
                return [
                    'id' => $location->id,
                    'name' => $location->location_name,
                    'court_count' => $location->court_count,
                ];
            }),
            'total_game_slots' => $schedule->gameSlots()->count(),
            'assigned_slots' => $schedule->gameSlots()->where('status', 'assigned')->count(),
            'available_slots' => $schedule->gameSlots()->where('status', 'available')->count(),
            'blocked_slots' => $schedule->gameSlots()->where('is_block', true)->count(),
            'created_at' => $schedule->created_at->format('Y-m-d H:i:s')
        ];
    }
}
