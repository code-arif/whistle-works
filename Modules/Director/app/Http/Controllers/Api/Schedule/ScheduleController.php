<?php

namespace Modules\Director\Http\Controllers\Api\Schedule;

use Exception;
use Carbon\Carbon;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Director\Models\Crew;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\Director\Models\GameSlotAssignment;
use Modules\Director\Http\Requests\{ScheduleCreateRequest, LocationAddRequest};
use Modules\Director\Models\{Camp, Schedule, ScheduleLocation, ScheduleTimeRange, GameSlot};

class ScheduleController extends Controller
{
    use ApiResponse;

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

        // Generate all dates between start and end
        $startDate = Carbon::parse($camp->start_date);
        $endDate = Carbon::parse($camp->end_date);

        $dates = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dates[] = [
                'date' => $currentDate->format('Y-m-d'),
                'day' => $currentDate->format('l'), // Monday, Tuesday, etc.
                'formatted' => $currentDate->format('M d, Y')
            ];
            $currentDate->addDay();
        }

        return $this->success(
            'Camp date range fetched successfully.',
            [
                'camp_id' => $camp->id,
                'camp_name' => $camp->camp_name,
                'start_date' => $camp->start_date,
                'end_date' => $camp->end_date,
                'total_days' => count($dates),
                'checked_in_referees_count' => $camp->checked_in_referees_count,
                'dates' => $dates
            ],
            200
        );
    }

    /**
     * Create schedule with time ranges and locations
     */
    // public function createSchedule(ScheduleCreateRequest $request, $campId)
    // {
    //     $user = auth('api')->user();

    //     // Verify camp ownership
    //     $camp = Camp::where('id', $campId)
    //         ->where('director_id', $user->id)
    //         ->first();

    //     if (!$camp) {
    //         return $this->error([], 'Camp not found.', 404);
    //     }

    //     // Check if schedule already exists
    //     if ($camp->schedule()->exists()) {
    //         return $this->error([], 'Schedule already exists for this camp. Delete existing schedule first.', 400);
    //     }

    //     // Validate dates are within camp range
    //     foreach ($request->time_ranges as $range) {
    //         $rangeDate = Carbon::parse($range['date']);
    //         $campStart = Carbon::parse($camp->start_date);
    //         $campEnd = Carbon::parse($camp->end_date);

    //         if ($rangeDate->lt($campStart) || $rangeDate->gt($campEnd)) {
    //             return $this->error([], "Date {$range['date']} is outside camp date range.", 400);
    //         }
    //     }

    //     DB::beginTransaction();
    //     try {
    //         // Create schedule
    //         $schedule = Schedule::create([
    //             'camp_id' => $camp->id,
    //             'game_duration' => $request->game_duration,
    //             'max_referees_per_slot' => $request->max_referees_per_slot,
    //             'status' => 'draft'
    //         ]);

    //         // Create time ranges
    //         foreach ($request->time_ranges as $range) {
    //             ScheduleTimeRange::create([
    //                 'schedule_id' => $schedule->id,
    //                 'date' => $range['date'],
    //                 'start_time' => $range['start_time'],
    //                 'end_time' => $range['end_time']
    //             ]);
    //         }

    //         // Create locations with courts
    //         foreach ($request->locations as $location) {
    //             ScheduleLocation::create([
    //                 'schedule_id' => $schedule->id,
    //                 'location_name' => $location['location_name'],
    //                 'latitude' => $location['latitude'] ?? null,
    //                 'longitude' => $location['longitude'] ?? null,
    //                 'court_count' => $location['court_count']
    //             ]);
    //         }

    //         // Generate game slots
    //         $slotsGenerated = $this->generateGameSlots($schedule, $locationMap);

    //         DB::commit();

    //         return $this->success(
    //             'Schedule created successfully.',
    //             array_merge(
    //                 $this->formatScheduleResponse($schedule),
    //                 ['slots_generated' => $slotsGenerated]
    //             ),
    //             201
    //         );
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return $this->error('Failed to create schedule: ' . $e->getMessage(), null, 500);
    //     }
    // }

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

        // Validate dates are within camp range
        foreach ($request->time_ranges as $range) {
            $rangeDate = Carbon::parse($range['date']);
            $campStart = Carbon::parse($camp->start_date);
            $campEnd = Carbon::parse($camp->end_date);

            if ($rangeDate->lt($campStart) || $rangeDate->gt($campEnd)) {
                return $this->error("Date {$range['date']} is outside camp date range.", null, 400);
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

            // Create time ranges
            foreach ($request->time_ranges as $range) {
                ScheduleTimeRange::create([
                    'schedule_id' => $schedule->id,
                    'date' => $range['date'],
                    'start_time' => $range['start_time'],
                    'end_time' => $range['end_time']
                ]);
            }

            // CREATE LOCATIONS AND STORE THEM
            $locationMap = [];
            foreach ($request->locations as $location) {
                $scheduleLocation = ScheduleLocation::create([
                    'schedule_id' => $schedule->id,
                    'location_name' => $location['location_name'],
                    'latitude' => $location['latitude'] ?? null,
                    'longitude' => $location['longitude'] ?? null,
                    'court_count' => $location['court_count']
                ]);

                // STORE CREATED LOCATION
                $locationMap[] = $scheduleLocation;
            }

            // PASS BOTH PARAMETERS
            $slotsGenerated = $this->generateGameSlots($schedule, $locationMap);

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
     * Generate game slots based on time ranges, game duration, and courts
     *
     * Logic:
     * - For each date with time range
     * - Calculate how many games fit in that time (duration-based)
     * - For each game time slot, create entries for ALL courts
     */
    // private function generateGameSlots(Schedule $schedule)
    // {
    //     $timeRanges = $schedule->timeRanges;
    //     $locations = $schedule->locations;
    //     $gameDuration = $schedule->game_duration; // in minutes

    //     $totalSlotsCreated = 0;

    //     foreach ($timeRanges as $range) {
    //         $startTime = Carbon::parse($range->start_time);
    //         $endTime = Carbon::parse($range->end_time);
    //         $gameDate = $range->date;

    //         // Calculate how many games can fit
    //         $currentTime = $startTime->copy();

    //         while ($currentTime->copy()->addMinutes($gameDuration)->lte($endTime)) {
    //             $slotStartTime = $currentTime->format('H:i:s');
    //             $slotEndTime = $currentTime->copy()->addMinutes($gameDuration)->format('H:i:s');

    //             // Create slots for ALL locations and ALL courts
    //             foreach ($locations as $location) {
    //                 for ($courtNum = 1; $courtNum <= $location->court_count; $courtNum++) {
    //                     GameSlot::create([
    //                         'schedule_id' => $schedule->id,
    //                         'schedule_location_id' => $location->id,
    //                         'game_date' => $gameDate,
    //                         'start_time' => $slotStartTime,
    //                         'end_time' => $slotEndTime,
    //                         'court_name' => $location->location_name . ' - Court ' . $courtNum,
    //                         'court_number' => $courtNum,
    //                         'status' => 'available',
    //                         'is_block' => false
    //                     ]);

    //                     $totalSlotsCreated++;
    //                 }
    //             }

    //             // Move to next time slot
    //             $currentTime->addMinutes($gameDuration);
    //         }
    //     }

    //     return $totalSlotsCreated;
    // }

    /**
     * Generate game slots - LOCATION SPECIFIC
     */
    private function generateGameSlots(Schedule $schedule, $locationMap)
    {
        $timeRanges = $schedule->timeRanges;
        $gameDuration = $schedule->game_duration;
        $totalSlotsCreated = 0;

        foreach ($timeRanges as $range) {
            $startTime = Carbon::parse($range->start_time);
            $endTime = Carbon::parse($range->end_time);
            $gameDate = $range->date;

            $currentTime = $startTime->copy();

            // Generate time slots
            while ($currentTime->copy()->addMinutes($gameDuration)->lte($endTime)) {
                $slotStartTime = $currentTime->format('H:i:s');
                $slotEndTime = $currentTime->copy()->addMinutes($gameDuration)->format('H:i:s');

                // Create slots for EACH location with its OWN court count
                foreach ($locationMap as $location) {
                    for ($courtNum = 1; $courtNum <= $location->court_count; $courtNum++) {
                        GameSlot::create([
                            'schedule_id' => $schedule->id,
                            'schedule_location_id' => $location->id,
                            'game_date' => $gameDate,
                            'start_time' => $slotStartTime,
                            'end_time' => $slotEndTime,
                            'court_name' => "Court {$courtNum}", // Default naming
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
     * This is for the UI grid display
     */
    // public function getGameSlots($campId, Request $request)
    // {
    //     $user = auth('api')->user();

    //     $camp = Camp::where('director_id', $user->id)->find($campId);

    //     if (!$camp) {
    //         return $this->error('Camp not found.', null, 404);
    //     }

    //     $schedule = $camp->schedule;
    //     if (!$schedule) {
    //         return $this->error('Schedule not found.', null, 404);
    //     }

    //     // Get available dates
    //     $availableDates = $schedule->timeRanges()
    //         ->orderBy('date')
    //         ->get()
    //         ->map(function ($range) {
    //             return [
    //                 'date' => $range->date,
    //                 'formatted' => Carbon::parse($range->date)->format('F d'),
    //                 'day' => Carbon::parse($range->date)->format('l')
    //             ];
    //         });

    //     // Get date from request or use first date
    //     $selectedDate = $request->date ?? $availableDates->first()['date'];

    //     // Get all game slots for selected date with proper eager loading
    //     $gameSlots = GameSlot::where('schedule_id', $schedule->id)
    //         ->where('game_date', $selectedDate)
    //         ->with([
    //             'location',
    //             'slotAssignments.assignable' => function ($query) {
    //                 // Eager load crew members when assignable is Crew
    //                 $query->when(function ($q) {
    //                     return $q->getModel() instanceof Crew;
    //                 }, function ($q) {
    //                     $q->with('members');
    //                 });
    //             }
    //         ])
    //         ->orderBy('start_time')
    //         ->orderBy('court_number')
    //         ->get();

    //     $scheduleFormat = [
    //         'schedule_id' => $schedule->id,
    //         "max_referees_per_slot" => $schedule->max_referees_per_slot,
    //         "status" => $schedule->status,
    //     ];

    //     $scheduleFormat = [
    //         'schedule_id' => $schedule->id,
    //         "max_referees_per_slot" => $schedule->max_referees_per_slot,
    //         "status" => $schedule->status,
    //     ];

    //     // Group by time
    //     $timeSlots = $gameSlots->groupBy('start_time')->map(function ($slots, $time) {
    //         return [
    //             'time' => Carbon::parse($time)->format('h:i A'),
    //             'time_24h' => $time,
    //             'courts' => $slots->map(function ($slot) {
    //                 return [
    //                     'slot_id' => $slot->id,
    //                     'court_name' => $slot->court_name,
    //                     'court_number' => $slot->court_number,
    //                     'location' => $slot->location->location_name,
    //                     'status' => $slot->status,
    //                     'is_blocked' => $slot->is_block,
    //                     'start_time' => $slot->start_time,
    //                     'end_time' => $slot->end_time,
    //                     'assignments_count' => $slot->slotAssignments->count(),
    //                     'assignments' => $slot->slotAssignments->map(function ($assignment) {
    //                         if ($assignment->assignment_type === 'crew') {
    //                             $crew = $assignment->assignable;

    //                             // Get crew members with their details
    //                             $members = $crew->members->map(function ($member) {
    //                                 return [
    //                                     'referee_id' => $member->id,
    //                                     'referee_name' => $member->first_name . ' ' . $member->last_name,
    //                                     'avatar' => $member->avatar ? asset('' . $member->avatar) : asset('default/profile.jpg'),
    //                                     'email' => $member->email,
    //                                 ];
    //                             });

    //                             return [
    //                                 'assignment_id' => $assignment->id,
    //                                 'type' => 'crew',
    //                                 'crew_id' => $crew->id,
    //                                 'crew_name' => $crew->name ?? 'Unknown',
    //                                 'member_count' => $crew->members->count(),
    //                                 'members' => $members
    //                             ];
    //                         } else {
    //                             // Individual referee
    //                             return [
    //                                 'type' => 'individual',
    //                                 'assignment_id' => $assignment->id,
    //                                 'referee_id' => $assignment->assignable->id,
    //                                 'referee_name' => ($assignment->assignable->first_name ?? '') . ' ' . ($assignment->assignable->last_name ?? ''),
    //                                 'avatar' => $assignment->assignable->avatar
    //                                     ? asset('/' . $assignment->assignable->avatar)
    //                                     : asset('default/profile.jpg'),
    //                             ];
    //                         }
    //                     })
    //                 ];
    //             })->values()
    //         ];
    //     })->values();

    //     // Get unique court names for header
    //     $courtHeaders = $gameSlots->unique('court_name')
    //         ->sortBy('court_number')
    //         ->map(function ($slot) {
    //             return [
    //                 'location' => $slot->location->location_name,
    //                 'court_name' => $slot->court_name,
    //                 'court_number' => $slot->court_number
    //             ];
    //         })
    //         ->values();

    //     return $this->success(
    //         'Game slots fetched successfully.',
    //         [
    //             'schedule' => $scheduleFormat,
    //             'selected_date' => $selectedDate,
    //             'selected_date_formatted' => Carbon::parse($selectedDate)->format('F d, Y'),
    //             'available_dates' => $availableDates,
    //             'court_headers' => $courtHeaders,
    //             'time_slots' => $timeSlots,
    //             'total_slots' => $gameSlots->count(),
    //             'assigned_slots' => $gameSlots->where('status', 'assigned')->count()
    //         ],
    //         200
    //     );
    // }


    /**
     * Get game slots grouped by date, time, and court
     * This is for the UI grid display
     *
     * Query params:
     * - date: Filter by specific date (optional, defaults to first date)
     * - location_id: Filter by specific location (optional, shows all if not provided)
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

        // Get available dates
        $availableDates = $schedule->timeRanges()
            ->orderBy('date')
            ->get()
            ->map(function ($range) {
                return [
                    'date' => $range->date,
                    'formatted' => Carbon::parse($range->date)->format('F d'),
                    'day' => Carbon::parse($range->date)->format('l')
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
            return $this->error(
                'Invalid location ID.',
                [
                    'provided_location_id' => $selectedLocationId,
                    'available_locations' => $availableLocations
                ],
                400
            );
        }

        // Build query for game slots
        $gameSlotsQuery = GameSlot::where('schedule_id', $schedule->id)
            ->where('game_date', $selectedDate)
            ->with([
                'location',
                'slotAssignments.assignable' => function ($query) {
                    // Eager load crew members when assignable is Crew
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

        // Group by time
        $timeSlots = $gameSlots->groupBy('start_time')->map(function ($slots, $time) {
            return [
                'time' => Carbon::parse($time)->format('h:i A'),
                'time_24h' => $time,
                'courts' => $slots->map(function ($slot) {
                    return [
                        'slot_id' => $slot->id,
                        'court_name' => $slot->court_name,
                        'court_number' => $slot->court_number,
                        'location' => $slot->location->location_name,
                        'location_id' => $slot->location->id, // Added for reference
                        'status' => $slot->status,
                        'is_blocked' => $slot->is_block,
                        'start_time' => $slot->start_time,
                        'end_time' => $slot->end_time,
                        'assignments_count' => $slot->slotAssignments->count(),
                        'assignments' => $slot->slotAssignments->map(function ($assignment) {
                            if ($assignment->assignment_type === 'crew') {
                                $crew = $assignment->assignable;

                                // Get crew members with their details
                                $members = $crew->members->map(function ($member) {
                                    return [
                                        'referee_id' => $member->id,
                                        'referee_name' => $member->first_name . ' ' . $member->last_name,
                                        'avatar' => $member->avatar ? asset('' . $member->avatar) : asset('default/profile.jpg'),
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
                                // Individual referee
                                return [
                                    'type' => 'individual',
                                    'assignment_id' => $assignment->id,
                                    'referee_id' => $assignment->assignable->id,
                                    'referee_name' => ($assignment->assignable->first_name ?? '') . ' ' . ($assignment->assignable->last_name ?? ''),
                                    'avatar' => $assignment->assignable->avatar
                                        ? asset('' . $assignment->assignable->avatar)
                                        : asset('default/profile.jpg'),
                                ];
                            }
                        })
                    ];
                })->values()
            ];
        })->values();

        // Get unique court names for header (filtered by location if applicable)
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
                'selected_date' => $selectedDate,
                'selected_date_formatted' => Carbon::parse($selectedDate)->format('F d, Y'),
                'available_dates' => $availableDates,

                // Location filter data
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
            // Remove all assignments using new table
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

        $schedule->delete(); // Cascade will handle related records

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
            'max_referees_per_slot' => $schedule->max_referees_per_slot,
            'time_ranges' => $schedule->timeRanges->map(function ($range) {
                return [
                    'date' => $range->date,
                    'formatted_date' => Carbon::parse($range->date)->format('M d, Y'),
                    'start_time' => Carbon::parse($range->start_time)->format('h:i A'),
                    'end_time' => Carbon::parse($range->end_time)->format('h:i A'),
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
