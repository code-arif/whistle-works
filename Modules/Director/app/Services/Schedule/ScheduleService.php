<?php

namespace Modules\Director\Services\Schedule;

use App\Models\AssistantDirectorPermission;
use App\Models\CampEvaluatorRegistration;
use App\Models\CampRefereeJearsyNumber;
use App\Notifications\SchedulePublishNotification;
use App\Notifications\ScheduleUpdatedNotification;
use App\Services\LocationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Director\Http\Requests\ScheduleCreateRequest;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Modules\Director\Models\Crew;
use Modules\Director\Models\GameSlot;
use Modules\Director\Models\GameSlotAssignment;
use Modules\Director\Models\GameSlotAssignmentPosition;
use Modules\Director\Models\Schedule;
use Modules\Director\Models\ScheduleLocation;
use Modules\Director\Models\ScheduleTimeRange;

class ScheduleService
{
    protected LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Get camp date range for schedule creation.
     *
     * @param  mixed $user
     * @param  int   $campId
     * @return array
     */
    public function getCampDateRange($user, int $campId): array
    {
        $camp = Camp::forDirectorOrAssistant($user->id)
            ->where('id', $campId)
            ->withCount('checkedInReferees')
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        // Parse dates in camp's timezone
        $campTimezone = $camp->timezone ?? 'UTC';
        $startDate    = Carbon::parse($camp->start_date, $campTimezone)->startOfDay();
        $endDate      = Carbon::parse($camp->end_date, $campTimezone)->startOfDay();

        $dates       = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dates[] = [
                'date'      => $currentDate->format('Y-m-d'),
                'day'       => $currentDate->format('l'),
                'formatted' => $currentDate->format('M d, Y'),
            ];
            $currentDate->addDay();
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Camp date range fetched successfully.',
            'data'    => [
                'camp_id'                   => $camp->id,
                'camp_name'                 => $camp->camp_name,
                'timezone'                  => $campTimezone,
                'timezone_name'             => $camp->timezone_display_name,
                'start_date'                => $startDate->toDateString(),
                'end_date'                  => $endDate->toDateString(),
                'total_days'                => count($dates),
                'checked_in_referees_count' => $camp->checked_in_referees_count,
                'dates'                     => $dates,
            ],
        ];
    }

    /**
     * Create schedule.
     *
     * @param  mixed                 $user
     * @param  int                   $campId
     * @param  ScheduleCreateRequest $request
     * @return array
     */
    public function createSchedule($user, int $campId, ScheduleCreateRequest $request): array
    {
        $camp = Camp::forDirectorOrAssistant($user->id)
            ->where('id', $campId)
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        if ($camp->director_id !== $user->id) {
            $hasPermission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->where('build_schedule', true)
                ->exists();

            if (!$hasPermission) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to build schedule for this camp.',
                    'data'    => null,
                ];
            }
        }

        if ($camp->schedule()->exists()) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Schedule already exists for this camp. Delete existing schedule first.',
                'data'    => null,
            ];
        }

        // Convert to Y-m-d string format
        $campStartDate = Carbon::parse($camp->start_date)->format('Y-m-d');
        $campEndDate   = Carbon::parse($camp->end_date)->format('Y-m-d');

        // Validate dates are within camp range
        foreach ($request->time_ranges as $range) {
            $rangeDate = $range['date'];

            if ($rangeDate < $campStartDate || $rangeDate > $campEndDate) {
                return [
                    'success' => false,
                    'code'    => 400,
                    'message' => "Date {$rangeDate} is outside camp date range ({$campStartDate} to {$campEndDate}).",
                    'data'    => null,
                ];
            }
        }

        DB::beginTransaction();
        try {
            // Create schedule
            $schedule = Schedule::create([
                'camp_id'               => $camp->id,
                'game_duration'         => $request->game_duration,
                'max_referees_per_slot' => $request->max_referees_per_slot,
                'mode'                  => $request->input('mode', 'individual'),
                'status'                => 'draft',
            ]);

            // Create time ranges
            foreach ($request->time_ranges as $range) {
                if (
                    !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $range['start_time']) ||
                    !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $range['end_time'])
                ) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'code'    => 400,
                        'message' => 'Invalid time format. Use HH:MM format (e.g., 09:00, 17:30).',
                        'data'    => null,
                    ];
                }

                if ($range['end_time'] <= $range['start_time']) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'code'    => 400,
                        'message' => 'End time must be after start time.',
                        'data'    => null,
                    ];
                }

                ScheduleTimeRange::create([
                    'schedule_id' => $schedule->id,
                    'date'        => $range['date'],
                    'start_time'  => $range['start_time'] . ':00',
                    'end_time'    => $range['end_time'] . ':00',
                ]);
            }

            // Create locations with proper name from coordinates
            $locationMap = [];
            foreach ($request->locations as $index => $location) {
                $locationName = $location['location_name'] ?? null;
                $latitude     = $location['latitude'] ?? null;
                $longitude    = $location['longitude'] ?? null;
                $address      = $location['address'] ?? null;

                if ($latitude && $longitude) {
                    if (!$this->locationService->validateCoordinates($latitude, $longitude)) {
                        DB::rollBack();
                        return [
                            'success' => false,
                            'code'    => 400,
                            'message' => "Invalid coordinates for location #" . ($index + 1),
                            'data'    => null,
                        ];
                    }

                    $locationResult = $this->locationService->getLocationFromCoordinates(
                        $latitude,
                        $longitude,
                        'detailed'
                    );

                    if ($locationResult['success']) {
                        if (empty($locationName) || strlen($locationName) > 100) {
                            $locationName = $locationResult['location_name'];
                        }
                    } else {
                        if (empty($locationName)) {
                            $locationName = "Location " . ($index + 1);
                        }
                    }
                } else {
                    if (empty($locationName)) {
                        DB::rollBack();
                        return [
                            'success' => false,
                            'code'    => 400,
                            'message' => "Location name or coordinates required for location #" . ($index + 1),
                            'data'    => null,
                        ];
                    }
                }

                $scheduleLocation = ScheduleLocation::create([
                    'schedule_id'   => $schedule->id,
                    'location_name' => $locationName,
                    'latitude'      => $latitude,
                    'longitude'     => $longitude,
                    'court_count'   => $location['court_count'] ?? 1,
                    'address'       => $address ?? null,
                ]);

                $locationMap[] = $scheduleLocation;
            }

            // Generate game slots with referee positions
            $refereePositions = $request->input('referee_positions', []);
            $slotsGenerated   = $this->generateGameSlots($schedule, $locationMap, $camp, $refereePositions);

            DB::commit();

            return [
                'success' => true,
                'code'    => 201,
                'message' => 'Schedule created successfully.',
                'data'    => array_merge(
                    $this->formatScheduleResponse($schedule),
                    ['slots_generated' => $slotsGenerated]
                ),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to create schedule: ' . $e->getMessage(),
                'data'    => null,
            ];
        }
    }

    /**
     * Generate game slots in camp timezone.
     */
    private function generateGameSlots(Schedule $schedule, array $locationMap, Camp $camp, array $refereePositions = []): int
    {
        $timeRanges        = $schedule->timeRanges;
        $gameDuration      = $schedule->game_duration;
        $totalSlotsCreated = 0;
        $campTimezone      = $camp->timezone ?? 'UTC';

        if (empty($refereePositions) && $schedule->max_referees_per_slot > 0) {
            for ($i = 1; $i <= $schedule->max_referees_per_slot; $i++) {
                $refereePositions[] = "Referee {$i}";
            }
        }

        foreach ($timeRanges as $range) {
            $gameDate    = $range->date;
            $startTime   = Carbon::parse($range->start_time, $campTimezone);
            $endTime     = Carbon::parse($range->end_time, $campTimezone);
            $currentTime = $startTime->copy();

            while ($currentTime->copy()->addMinutes($gameDuration)->lte($endTime)) {
                $slotStartTime = $currentTime->format('H:i:s');
                $slotEndTime   = $currentTime->copy()->addMinutes($gameDuration)->format('H:i:s');

                foreach ($locationMap as $location) {
                    for ($courtNum = 1; $courtNum <= $location->court_count; $courtNum++) {
                        $slot = GameSlot::create([
                            'schedule_id'          => $schedule->id,
                            'schedule_location_id' => $location->id,
                            'game_date'            => $gameDate,
                            'start_time'           => $slotStartTime,
                            'end_time'             => $slotEndTime,
                            'court_name'           => "Court {$courtNum}",
                            'court_number'         => $courtNum,
                            'status'               => 'available',
                            'mode'                 => $schedule->mode ?? 'individual',
                            'is_block'             => false,
                        ]);

                        foreach ($refereePositions as $position) {
                            GameSlotAssignmentPosition::create([
                                'camp_id'                  => $camp->id,
                                'game_slot_id'             => $slot->id,
                                'game_slot_assignment_id'  => null,
                                'position'                 => $position,
                            ]);
                        }

                        $totalSlotsCreated++;
                    }
                }

                $currentTime->addMinutes($gameDuration);
            }
        }

        return $totalSlotsCreated;
    }

    /**
     * Get schedule details.
     *
     * @param  mixed   $user
     * @param  int     $campId
     * @param  Request $request
     * @return array
     */
    public function getSchedule($user, int $campId, Request $request): array
    {
        $camp = Camp::forDirectorOrAssistant($user->id)
            ->where('id', $campId)
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        $schedule = $camp->schedule()
            ->with(['locations', 'timeRanges', 'gameSlots'])
            ->first();

        if (!$schedule) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Schedule not found.',
                'data'    => null,
            ];
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Schedule fetched successfully.',
            'data'    => $this->formatScheduleResponse($schedule),
        ];
    }

    /**
     * Get game slots grouped by date, time, and court.
     *
     * @param  mixed   $user
     * @param  int     $campId
     * @param  Request $request
     * @return array
     */
    public function getGameSlots($user, int $campId, Request $request): array
    {
        $camp = Camp::forDirectorOrAssistant($user->id)->find($campId);

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        $jerseyNumbers = CampRefereeJearsyNumber::where('camp_id', $camp->id)
            ->pluck('jersey_number', 'referee_id');

        $schedule = $camp->schedule;
        if (!$schedule) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Schedule not found.',
                'data'    => null,
            ];
        }

        $campTimezone = $camp->timezone ?? 'UTC';

        // Get available dates
        $availableDates = $schedule->timeRanges()
            ->orderBy('date')
            ->get()
            ->map(function ($range) use ($campTimezone) {
                $date = Carbon::parse($range->date, $campTimezone);
                return [
                    'date'      => $range->date,
                    'formatted' => $date->format('F d'),
                    'day'       => $date->format('l'),
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
                    'location_id'   => $location->id,
                    'location_name' => $location->location_name,
                    'court_count'   => $location->court_count,
                    'address'       => $location->address ?? null,
                ];
            });

        $selectedLocationId = $request->location_id ?? null;

        if ($selectedLocationId && !$availableLocations->contains('location_id', $selectedLocationId)) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Invalid location ID.',
                'data'    => [
                    'provided_location_id' => $selectedLocationId,
                    'available_locations'  => $availableLocations,
                ],
            ];
        }

        // Build query for game slots
        $gameSlotsQuery = GameSlot::where('schedule_id', $schedule->id)
            ->where('game_date', $selectedDate)
            ->with([
                'location',
                'assignmentPositions.gameSlotAssignment.assignable',
                'slotAssignments.assignable' => function ($query) {
                    $query->when(function ($q) {
                        return $q->getModel() instanceof Crew;
                    }, function ($q) {
                        $q->with('members');
                    });
                },
            ]);

        if ($selectedLocationId) {
            $gameSlotsQuery->where('schedule_location_id', $selectedLocationId);
        }

        $gameSlots = $gameSlotsQuery
            ->orderBy('start_time')
            ->orderBy('court_number')
            ->get();

        // Determine active mode for schedule
        $activeMode = $schedule->mode;
        if (!$activeMode) {
            $activeMode = $gameSlots->firstWhere('status', 'available')?->mode
                ?? $gameSlots->first()?->mode
                ?? $schedule->gameSlots()->latest('id')->value('mode')
                ?? 'individual';
        }

        $scheduleFormat = [
            'schedule_id'           => $schedule->id,
            'max_referees_per_slot' => $schedule->max_referees_per_slot,
            'status'                => $schedule->status,
            'mode'                  => $activeMode,
            'active_mode'           => $activeMode,
        ];

        // Group by time and format
        $timeSlots = $gameSlots->groupBy('start_time')
            ->map(function ($slots, $time) use ($campTimezone, $jerseyNumbers) {
                $timeCarbon = Carbon::parse($time, $campTimezone);

                return [
                    'time'     => $timeCarbon->format('h:i A'),
                    'time_24h' => $time,
                    'courts'   => $slots->map(function ($slot) use ($campTimezone, $jerseyNumbers) {
                        $startTime = Carbon::parse($slot->start_time, $campTimezone);
                        $endTime   = Carbon::parse($slot->end_time, $campTimezone);

                        return [
                            'slot_id'            => $slot->id,
                            'court_name'         => $slot->court_name,
                            'court_number'       => $slot->court_number,
                            'location'           => $slot->location->location_name,
                            'address'            => $slot->location->address,
                            'location_id'        => $slot->location->id,
                            'status'             => $slot->status,
                            'is_blocked'         => $slot->is_block,
                            'mode'               => $slot->mode,
                            'start_time'         => $startTime->format('H:i'),
                            'end_time'           => $endTime->format('H:i'),
                            'start_time_display' => $startTime->format('h:i A'),
                            'end_time_display'   => $endTime->format('h:i A'),
                            'positions'          => $slot->assignmentPositions->map(function ($pos) use ($jerseyNumbers) {
                                $assignment   = $pos->gameSlotAssignment;
                                $referee      = $assignment?->assignable;
                                $jerseyNumber = $referee ? ($jerseyNumbers[$referee->id] ?? null) : null;

                                return [
                                    'id'                      => $pos->id,
                                    'position'                => $pos->position,
                                    'game_slot_assignment_id' => $pos->game_slot_assignment_id,
                                    'is_assigned'             => !is_null($pos->game_slot_assignment_id),
                                    'referee'                 => $referee ? [
                                        'referee_id'    => $referee->id,
                                        'referee_name'  => trim(($referee->first_name ?? '') . ' ' . ($referee->last_name ?? '')),
                                        'jersey_number' => $jerseyNumber,
                                        'email'         => $referee->email ?? null,
                                        'avatar'        => $referee->avatar ? asset($referee->avatar) : asset('default/profile.jpg'),
                                    ] : null,
                                ];
                            })->values(),
                            'assignments_count'  => $slot->slotAssignments->count(),
                            'assignments'        => $slot->slotAssignments->map(function ($assignment) use ($jerseyNumbers) {
                                if ($assignment->assignment_type === 'crew') {
                                    $crew    = $assignment->assignable;
                                    $members = $crew->members->map(function ($member) use ($jerseyNumbers) {
                                        return [
                                            'referee_id'    => $member->id,
                                            'referee_name'  => $member->first_name . ' ' . $member->last_name,
                                            'avatar'        => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                                            'email'         => $member->email,
                                            'jersey_number' => $jerseyNumbers[$member->id] ?? null,
                                        ];
                                    });

                                    return [
                                        'assignment_id' => $assignment->id,
                                        'type'          => 'crew',
                                        'crew_id'       => $crew->id,
                                        'crew_name'     => $crew->name ?? 'Unknown',
                                        'member_count'  => $crew->members->count(),
                                        'members'       => $members,
                                    ];
                                } else {
                                    $referee      = $assignment->assignable;
                                    $jerseyNumber = $referee ? ($jerseyNumbers[$referee->id] ?? null) : null;

                                    return [
                                        'type'          => 'individual',
                                        'assignment_id' => $assignment->id,
                                        'position'      => $assignment->position,
                                        'referee_id'    => $referee?->id,
                                        'referee_name'  => $referee
                                            ? trim(($referee->first_name ?? '') . ' ' . ($referee->last_name ?? ''))
                                            : null,
                                        'jourcy_number' => $jerseyNumber,
                                        'avatar'        => $referee?->avatar
                                            ? asset($referee->avatar)
                                            : asset('default/profile.jpg'),
                                    ];
                                }
                            }),
                        ];
                    })->values(),
                ];
            })->values();

        // Unique court headers
        $courtHeaders = $gameSlots->unique(function ($slot) {
            return $slot->location->id . '-' . $slot->court_number;
        })
            ->sortBy('court_number')
            ->map(function ($slot) {
                return [
                    'location'     => $slot->location->location_name,
                    'location_id'  => $slot->location->id,
                    'court_name'   => $slot->court_name,
                    'court_number' => $slot->court_number,
                    'address'      => $slot->location->address,
                ];
            })
            ->values();

        $selectedLocation = null;
        if ($selectedLocationId) {
            $selectedLocation = $availableLocations->firstWhere('location_id', $selectedLocationId);
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Game slots fetched successfully.',
            'data'    => [
                'schedule'                => $scheduleFormat,
                'mode'                    => $activeMode,
                'active_mode'             => $activeMode,
                'camp_timezone'           => $campTimezone,
                'camp_timezone_name'      => $camp->timezone_display_name,
                'selected_date'           => $selectedDate,
                'selected_date_formatted' => Carbon::parse($selectedDate, $campTimezone)->format('F d, Y'),
                'available_dates'         => $availableDates,
                'available_locations'     => $availableLocations,
                'selected_location'       => $selectedLocation,
                'court_headers'           => $courtHeaders,
                'time_slots'              => $timeSlots,
                'total_slots'             => $gameSlots->count(),
                'assigned_slots'          => $gameSlots->where('status', 'assigned')->count(),
            ],
        ];
    }

    /**
     * Publish schedule and notify all registered referees and evaluators.
     *
     * @param  mixed $user
     * @param  int   $campId
     * @return array
     */
    public function publishSchedule($user, int $campId): array
    {
        $camp = Camp::forDirectorOrAssistant($user->id)
            ->where('id', $campId)
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        if ($camp->director_id !== $user->id) {
            $hasPermission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->where('publish_camp', true)
                ->exists();

            if (!$hasPermission) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to publish the schedule for this camp.',
                    'data'    => null,
                ];
            }
        }

        $schedule = $camp->schedule;
        if (!$schedule) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Schedule not found.',
                'data'    => null,
            ];
        }

        if ($schedule->status === 'published') {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Schedule is already published.',
                'data'    => null,
            ];
        }

        DB::beginTransaction();
        try {
            $schedule->update(['status' => 'published']);

            $registeredReferees = CampRefereeCheckin::where('camp_id', $campId)
                ->with('referee')
                ->get()
                ->pluck('referee')
                ->filter();

            $registeredEvaluators = collect();
            if (class_exists(CampEvaluatorRegistration::class)) {
                $registeredEvaluators = CampEvaluatorRegistration::where('camp_id', $campId)
                    ->with('evaluator')
                    ->get()
                    ->pluck('evaluator')
                    ->filter();
            }

            $allRecipients = $registeredReferees->merge($registeredEvaluators);

            if ($allRecipients->isNotEmpty()) {
                Notification::send(
                    $allRecipients,
                    new SchedulePublishNotification($camp, $user, $schedule)
                );
            }

            DB::commit();

            Log::info('Schedule published and notifications sent', [
                'director_id'               => $user->id,
                'camp_id'                   => $campId,
                'schedule_id'               => $schedule->id,
                'total_referees_notified'   => $registeredReferees->count(),
                'total_evaluators_notified' => $registeredEvaluators->count(),
                'total_recipients'          => $allRecipients->count(),
            ]);

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Schedule published successfully.',
                'data'    => [
                    'schedule'           => [
                        'id'          => $schedule->id,
                        'status'      => $schedule->status,
                        'total_slots' => $schedule->gameSlots()->count(),
                    ],
                    'notifications_sent' => [
                        'total_recipients' => $allRecipients->count(),
                        'referees'         => $registeredReferees->count(),
                        'evaluators'       => $registeredEvaluators->count(),
                    ],
                    'camp'               => [
                        'id'   => $camp->id,
                        'name' => $camp->camp_name,
                    ],
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to publish schedule', [
                'director_id' => $user->id,
                'camp_id'     => $campId,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to publish schedule: ' . $e->getMessage(),
                'data'    => null,
            ];
        }
    }

    /**
     * Clear schedule (remove all assignments but keep slots).
     *
     * @param  mixed $user
     * @param  int   $campId
     * @return array
     */
    public function clearSchedule($user, int $campId): array
    {
        $camp = Camp::forDirectorOrAssistant($user->id)
            ->where('id', $campId)
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        $schedule = $camp->schedule;
        if (!$schedule) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Schedule not found.',
                'data'    => null,
            ];
        }

        $wasPublished = $schedule->status === 'published';

        DB::beginTransaction();
        try {
            $slotIds = $schedule->gameSlots()->pluck('id');
            GameSlotAssignmentPosition::whereIn('game_slot_id', $slotIds)->update(['game_slot_assignment_id' => null]);
            GameSlotAssignment::whereIn('game_slot_id', $slotIds)->delete();

            $schedule->gameSlots()->update(['status' => 'available']);

            $notifiedCount = 0;
            if ($wasPublished) {
                $notifiedCount = $this->notifyScheduleUpdate($schedule, 'assignments_changed');
            }

            DB::commit();

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'All assignments cleared successfully.',
                'data'    => [
                    'notifications_sent' => $notifiedCount,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to clear schedule: ' . $e->getMessage(),
                'data'    => null,
            ];
        }
    }

    /**
     * Delete entire schedule.
     *
     * @param  mixed $user
     * @param  int   $campId
     * @return array
     */
    public function deleteSchedule($user, int $campId): array
    {
        $camp = Camp::forDirectorOrAssistant($user->id)
            ->where('id', $campId)
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        if ($camp->director_id !== $user->id) {
            $hasPermission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->where('build_schedule', true)
                ->exists();

            if (!$hasPermission) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to delete the schedule for this camp.',
                    'data'    => null,
                ];
            }
        }

        $schedule = $camp->schedule;
        if (!$schedule) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Schedule not found.',
                'data'    => null,
            ];
        }

        $wasPublished = $schedule->status === 'published';

        DB::beginTransaction();
        try {
            $notifiedCount = 0;
            if ($wasPublished) {
                $notifiedCount = $this->notifyScheduleUpdate($schedule, 'slots_removed');
            }

            $schedule->delete();

            DB::commit();

            $message = $wasPublished
                ? 'Schedule deleted successfully. All registered users have been notified.'
                : 'Schedule deleted successfully.';

            return [
                'success' => true,
                'code'    => 200,
                'message' => $message,
                'data'    => [
                    'notifications_sent' => $wasPublished ? ($notifiedCount ?? 0) : 0,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to delete schedule: ' . $e->getMessage(),
                'data'    => null,
            ];
        }
    }

    /**
     * Format schedule response with camp timezone.
     */
    private function formatScheduleResponse(Schedule $schedule): array
    {
        $camp         = $schedule->camp;
        $campTimezone = $camp->timezone ?? 'UTC';

        $refereePositions = GameSlotAssignmentPosition::whereHas('gameSlot', function ($query) use ($schedule) {
            $query->where('schedule_id', $schedule->id);
        })
            ->whereNotNull('position')
            ->distinct()
            ->pluck('position')
            ->values();

        return [
            'id'                    => $schedule->id,
            'camp_id'               => $schedule->camp_id,
            'game_duration'         => $schedule->game_duration,
            'status'                => $schedule->status,
            'mode'                  => $schedule->mode ?? 'individual',
            'active_mode'           => $schedule->mode ?? 'individual',
            'max_referees_per_slot' => $schedule->max_referees_per_slot,
            'referee_positions'     => $refereePositions,
            'camp_timezone'         => $campTimezone,
            'camp_timezone_name'    => $camp->timezone_display_name,
            'time_ranges'           => $schedule->timeRanges->map(function ($range) use ($campTimezone) {
                $date      = Carbon::parse($range->date, $campTimezone);
                $startTime = Carbon::parse($range->start_time, $campTimezone);
                $endTime   = Carbon::parse($range->end_time, $campTimezone);

                return [
                    'date'           => $range->date,
                    'formatted_date' => $date->format('M d, Y'),
                    'start_time'     => $startTime->format('h:i A'),
                    'end_time'       => $endTime->format('h:i A'),
                ];
            }),
            'locations'             => $schedule->locations->map(function ($location) {
                return [
                    'id'          => $location->id,
                    'name'        => $location->location_name,
                    'court_count' => $location->court_count,
                    'latitude'    => $location->latitude,
                    'longitude'   => $location->longitude,
                    'address'     => $location->address,
                ];
            }),
            'total_game_slots'      => $schedule->gameSlots()->count(),
            'assigned_slots'        => $schedule->gameSlots()->where('status', 'assigned')->count(),
            'available_slots'       => $schedule->gameSlots()->where('status', 'available')->count(),
            'blocked_slots'         => $schedule->gameSlots()->where('is_block', true)->count(),
            'created_at'            => $schedule->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Notify all registered referees/evaluators when schedule is updated after publishing.
     */
    private function notifyScheduleUpdate(Schedule $schedule, string $changeType = 'assignments_changed'): int
    {
        try {
            $camp     = $schedule->camp;
            $director = auth('api')->user();

            $registeredReferees = CampRefereeCheckin::where('camp_id', $camp->id)
                ->with('referee')
                ->get()
                ->pluck('referee')
                ->filter();

            $registeredEvaluators = collect();
            if (class_exists(CampEvaluatorRegistration::class)) {
                $registeredEvaluators = CampEvaluatorRegistration::where('camp_id', $camp->id)
                    ->with('evaluator')
                    ->get()
                    ->pluck('evaluator')
                    ->filter();
            }

            $allRecipients = $registeredReferees->merge($registeredEvaluators);

            if ($allRecipients->isNotEmpty()) {
                Notification::send(
                    $allRecipients,
                    new ScheduleUpdatedNotification($camp, $director, $schedule, $changeType)
                );

                Log::info('Schedule update notifications sent', [
                    'director_id' => $director->id,
                    'camp_id'     => $camp->id,
                    'schedule_id' => $schedule->id,
                    'change_type' => $changeType,
                    'recipients'  => $allRecipients->count(),
                ]);
            }

            return $allRecipients->count();
        } catch (Exception $e) {
            Log::error('Failed to send schedule update notifications', [
                'schedule_id' => $schedule->id,
                'error'       => $e->getMessage(),
            ]);
            return 0;
        }
    }
}
