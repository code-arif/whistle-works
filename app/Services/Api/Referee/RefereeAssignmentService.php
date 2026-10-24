<?php

namespace App\Services\Api\Referee;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Modules\Director\Models\Crew;
use Modules\Director\Models\GameSlot;
use Modules\Director\Models\GameSlotAssignment;

class RefereeAssignmentService
{
    /**
     * Determine the timing status of a game slot ('past', 'ongoing', 'upcoming').
     *
     * @param  GameSlot  $gameSlot
     * @param  Carbon|null  $now
     * @return string
     */
    public function getSlotTimingStatus(GameSlot $gameSlot, ?Carbon $now = null): string
    {
        $now = $now ?? Carbon::now();

        if ($gameSlot->status === 'completed') {
            return 'past';
        }

        $dateStr = $gameSlot->game_date instanceof \DateTimeInterface
            ? $gameSlot->game_date->format('Y-m-d')
            : (string) $gameSlot->game_date;

        try {
            $startDateTime = Carbon::parse($dateStr . ' ' . $gameSlot->start_time);
            $endDateTime   = Carbon::parse($dateStr . ' ' . $gameSlot->end_time);

            if ($endDateTime->lt($startDateTime)) {
                $endDateTime->addDay();
            }

            if ($now->gt($endDateTime)) {
                return 'past';
            }

            if ($now->gte($startDateTime) && $now->lte($endDateTime)) {
                return 'ongoing';
            }

            return 'upcoming';
        } catch (\Throwable $e) {
            $today = $now->toDateString();
            if ($dateStr < $today) {
                return 'past';
            } elseif ($dateStr > $today) {
                return 'upcoming';
            }
            return 'ongoing';
        }
    }

    /**
     * Get all game slots assigned to referee for a specific camp (individual & crew merged).
     *
     * @param  User  $referee
     * @param  int|string  $campId
     * @param  int  $perPage
     * @param  int  $page
     * @param  string|null  $filter
     * @return array
     */
    public function getCampAssignedSlots(User $referee, $campId, int $perPage = 15, int $page = 1, ?string $filter = null): array
    {
        if (!$referee->hasRole('referee')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only referees can access this endpoint.',
                'data'    => null,
            ];
        }

        $camp = Camp::with('schedule')->find($campId);
        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        if (!$camp->schedule) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'No schedule found for this camp.',
                'data'    => null,
            ];
        }

        if ($camp->schedule->status !== 'published') {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'This camp schedule is not published yet!',
                'data'    => null,
            ];
        }

        $checkin = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $referee->id)
            ->first();

        if (!$checkin) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'You are not registered for this camp.',
                'data'    => null,
            ];
        }

        // 1. Individual assignments
        $individualAssignments = GameSlotAssignment::with([
            'gameSlot.location',
            'gameSlot.schedule',
            'gameSlot.slotAssignments.assignable',
        ])
            ->where('assignable_type', User::class)
            ->where('assignable_id', $referee->id)
            ->where('assignment_type', 'individual')
            ->whereHas('gameSlot.schedule', fn($q) => $q->where('camp_id', $campId))
            ->get();

        // 2. Crew assignments
        $refereeCrewIds = DB::table('crew_members')
            ->where('referee_id', $referee->id)
            ->pluck('crew_id');

        $crewAssignments = collect();

        if ($refereeCrewIds->isNotEmpty()) {
            $crewAssignments = GameSlotAssignment::with([
                'gameSlot.location',
                'gameSlot.schedule',
                'gameSlot.slotAssignments.assignable',
            ])
                ->where('assignable_type', Crew::class)
                ->whereIn('assignable_id', $refereeCrewIds)
                ->where('assignment_type', 'crew')
                ->whereHas('gameSlot.schedule', fn($q) => $q->where('camp_id', $campId))
                ->get();
        }

        // 3. Merge & Deduplicate by game_slot_id
        $allAssignments = $individualAssignments->concat($crewAssignments)
            ->filter(fn($a) => $a->gameSlot !== null)
            ->unique('game_slot_id')
            ->values();

        // 4. Normalize filter keyword
        $normalizedFilter = null;
        if (!empty($filter)) {
            $f = strtolower(trim((string) $filter));
            if (in_array($f, ['past', 'completed', 'history', 'finished', 'previous'], true)) {
                $normalizedFilter = 'past';
            } elseif (in_array($f, ['ongoing', 'current', 'live', 'in_progress', 'present'], true)) {
                $normalizedFilter = 'ongoing';
            } elseif (in_array($f, ['upcoming', 'future', 'next'], true)) {
                $normalizedFilter = 'upcoming';
            }
        }

        if ($allAssignments->isEmpty()) {
            return [
                'success' => true,
                'code'    => 200,
                'message' => 'No game slots assigned to you in this camp yet.',
                'data'    => [
                    'camp'          => $this->formatCamp($camp),
                    'active_filter' => $normalizedFilter ?: 'all',
                    'statistics'    => [
                        'total_assigned_slots' => 0,
                        'upcoming_slots'       => 0,
                        'ongoing_slots'        => 0,
                        'past_slots'           => 0,
                        'completed_slots'      => 0,
                    ],
                    'total_assignments' => 0,
                    'game_slots'        => [],
                    'pagination'        => [
                        'total'        => 0,
                        'per_page'     => (int) $perPage,
                        'current_page' => (int) $page,
                        'last_page'    => 1,
                    ],
                ],
            ];
        }

        // 5. Calculate slot timing status using camp timezone if available
        $now = Carbon::now();
        if (!empty($camp->timezone)) {
            try {
                $now = Carbon::now($camp->timezone);
            } catch (\Throwable $e) {
                $now = Carbon::now();
            }
        }

        foreach ($allAssignments as $assignment) {
            $assignment->timing_status = $this->getSlotTimingStatus($assignment->gameSlot, $now);
        }

        // Calculate statistics across all assigned slots
        $totalSlots     = $allAssignments->count();
        $upcomingSlots  = $allAssignments->where('timing_status', 'upcoming')->count();
        $ongoingSlots   = $allAssignments->where('timing_status', 'ongoing')->count();
        $pastSlots      = $allAssignments->where('timing_status', 'past')->count();
        $completedSlots = $allAssignments->filter(fn($a) => $a->gameSlot->status === 'completed')->count();

        // 6. Apply filter
        if ($normalizedFilter) {
            $filteredAssignments = $allAssignments->where('timing_status', $normalizedFilter)->values();
        } else {
            $filteredAssignments = $allAssignments;
        }

        // 7. Sort: past slots descending (most recent first), upcoming/ongoing ascending
        if ($normalizedFilter === 'past') {
            $filteredAssignments = $filteredAssignments->sortByDesc(
                fn($a) => ($a->gameSlot->game_date instanceof \DateTimeInterface
                    ? $a->gameSlot->game_date->format('Y-m-d')
                    : (string) $a->gameSlot->game_date) . ' ' . $a->gameSlot->start_time
            )->values();
        } else {
            $filteredAssignments = $filteredAssignments->sortBy(
                fn($a) => ($a->gameSlot->game_date instanceof \DateTimeInterface
                    ? $a->gameSlot->game_date->format('Y-m-d')
                    : (string) $a->gameSlot->game_date) . ' ' . $a->gameSlot->start_time
            )->values();
        }

        // 8. Paginate filtered slots
        $total     = $filteredAssignments->count();
        $paginated = $filteredAssignments->forPage($page, $perPage);
        $lastPage  = $total > 0 ? (int) ceil($total / $perPage) : 1;

        // 9. Format slots
        $gameSlots = collect();

        foreach ($paginated as $assignment) {
            $gameSlot = $assignment->gameSlot;

            $allSlotAssignments = $gameSlot->slotAssignments;
            $crewMembersList    = [];
            $individualReferees = [];

            foreach ($allSlotAssignments as $slotAssignment) {
                if ($slotAssignment->assignment_type === 'crew') {
                    $crew = $slotAssignment->assignable;
                    if ($crew && $crew->members) {
                        foreach ($crew->members as $member) {
                            $crewMembersList[] = [
                                'id'        => $member->id,
                                'name'      => $member->first_name . ' ' . $member->last_name,
                                'avatar'    => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                                'email'     => $member->email,
                                'phone'     => $member->phone,
                                'type'      => 'crew_member',
                                'crew_name' => $crew->name ?? 'N/A',
                                'position'  => $member->pivot->position ?? null,
                                'is_me'     => $member->id === $referee->id,
                            ];
                        }
                    }
                } else {
                    $assignedReferee = $slotAssignment->assignable;
                    if (!$assignedReferee) {
                        continue;
                    }

                    $individualReferees[] = [
                        'id'       => $assignedReferee->id,
                        'name'     => $assignedReferee->first_name . ' ' . $assignedReferee->last_name,
                        'avatar'   => $assignedReferee->avatar ? asset($assignedReferee->avatar) : asset('default/profile.jpg'),
                        'email'    => $assignedReferee->email,
                        'phone'    => $assignedReferee->phone,
                        'type'     => 'individual',
                        'position' => $slotAssignment->position ?? null,
                        'is_me'    => $assignedReferee->id === $referee->id,
                    ];
                }
            }

            $allReferees = array_merge($crewMembersList, $individualReferees);
            $assignmentSource = $assignment->assignment_type === 'crew' ? 'crew' : 'individual';
            $crewNameIfVia    = ($assignmentSource === 'crew') ? ($assignment->assignable->name ?? null) : null;

            $gameSlots->push([
                'game_slot_id'      => $gameSlot->id,
                'assignment_source' => $assignmentSource,
                'timing_status'     => $assignment->timing_status,
                'crew_name'         => $crewNameIfVia,
                'game_details'      => [
                    'date'          => $gameSlot->game_date instanceof \DateTimeInterface
                        ? $gameSlot->game_date->toDateString()
                        : (string) $gameSlot->game_date,
                    'start_time'    => $gameSlot->start_time,
                    'end_time'      => $gameSlot->end_time,
                    'court_name'    => $gameSlot->court_name,
                    'court_number'  => $gameSlot->court_number,
                    'status'        => $gameSlot->status,
                    'timing_status' => $assignment->timing_status,
                    'is_blocked'    => (bool) $gameSlot->is_block,
                ],
                'location' => [
                    'id'        => $gameSlot->location->id ?? null,
                    'name'      => $gameSlot->location->location_name ?? 'N/A',
                    'latitude'  => $gameSlot->location->latitude ?? null,
                    'longitude' => $gameSlot->location->longitude ?? null,
                    'address'   => $gameSlot->location->address ?? null,
                ],
                'my_position'       => $assignment->position,
                'assigned_at'       => $assignment->assigned_at instanceof \DateTimeInterface
                    ? $assignment->assigned_at->format('Y-m-d H:i:s')
                    : ($assignment->assigned_at ? (string) $assignment->assigned_at : null),
                'is_auto_assigned'  => (bool) $assignment->is_auto_assigned,
                'assigned_referees' => [
                    'total'    => count($allReferees),
                    'referees' => $allReferees,
                ],
            ]);
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Your assigned game slots for this camp retrieved successfully.',
            'data'    => [
                'camp'          => $this->formatCamp($camp),
                'active_filter' => $normalizedFilter ?: 'all',
                'statistics'    => [
                    'total_assigned_slots' => $totalSlots,
                    'upcoming_slots'       => $upcomingSlots,
                    'ongoing_slots'        => $ongoingSlots,
                    'past_slots'           => $pastSlots,
                    'completed_slots'      => $completedSlots,
                ],
                'game_slots' => $gameSlots,
                'pagination' => [
                    'total'        => $total,
                    'per_page'     => (int) $perPage,
                    'current_page' => (int) $page,
                    'last_page'    => $lastPage,
                ],
            ],
        ];
    }

    /**
     * Get all game slots where the authenticated referee is assigned across all published camps.
     *
     * @param  User  $referee
     * @return array
     */
    public function getMyAssignedSlots(User $referee): array
    {
        if (!$referee->hasRole('referee')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only referees can access this endpoint.',
                'data'    => null,
            ];
        }

        $assignments = GameSlotAssignment::with([
            'gameSlot.location',
            'gameSlot.schedule.camp',
            'gameSlot.slotAssignments.assignable',
        ])
            ->where('assignable_type', User::class)
            ->where('assignable_id', $referee->id)
            ->where('assignment_type', 'individual')
            ->whereHas('gameSlot', function ($q) {
                $q->whereHas('schedule', function ($q2) {
                    $q2->where('status', 'published');
                });
            })
            ->orderBy('assigned_at', 'desc')
            ->get();

        if ($assignments->isEmpty()) {
            return [
                'success' => true,
                'code'    => 200,
                'message' => 'No game slots assigned to you yet.',
                'data'    => [
                    'total_assignments' => 0,
                    'game_slots'        => [],
                ],
            ];
        }

        $gameSlots = $assignments->map(function ($assignment) use ($referee) {
            $gameSlot = $assignment->gameSlot;
            $camp = $gameSlot->schedule->camp;

            $allAssignments = $gameSlot->slotAssignments;
            $crewAssignments = $allAssignments->where('assignment_type', 'crew');
            $individualAssignments = $allAssignments->where('assignment_type', 'individual');

            $crewMembers = [];
            if ($crewAssignments->isNotEmpty()) {
                foreach ($crewAssignments as $crewAssignment) {
                    $crew = $crewAssignment->assignable;
                    if ($crew && $crew->members) {
                        foreach ($crew->members as $member) {
                            $crewMembers[] = [
                                'id'        => $member->id,
                                'name'      => $member->first_name . ' ' . $member->last_name,
                                'avatar'    => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                                'email'     => $member->email,
                                'phone'     => $member->phone,
                                'type'      => 'crew_member',
                                'crew_name' => $crew->crew_name ?? 'N/A',
                                'position'  => $crewAssignment->position,
                            ];
                        }
                    }
                }
            }

            $individualReferees = $individualAssignments->map(function ($individualAssignment) use ($referee) {
                $assignedReferee = $individualAssignment->assignable;

                return [
                    'id'       => $assignedReferee->id,
                    'name'     => $assignedReferee->first_name . ' ' . $assignedReferee->last_name,
                    'avatar'   => $assignedReferee->avatar ? asset($assignedReferee->avatar) : asset('default/profile.jpg'),
                    'email'    => $assignedReferee->email,
                    'phone'    => $assignedReferee->phone,
                    'type'     => 'individual',
                    'position' => $individualAssignment->position,
                    'is_me'    => $assignedReferee->id === $referee->id,
                ];
            })->values()->toArray();

            $allReferees = array_merge($crewMembers, $individualReferees);

            return [
                'game_slot_id' => $gameSlot->id,
                'camp'         => [
                    'id'         => $camp->id,
                    'name'       => $camp->camp_name,
                    'logo'       => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                    'location'   => $camp->location,
                    'start_date' => $camp->start_date->toDateString(),
                    'end_date'   => $camp->end_date->toDateString(),
                    'address'    => $camp->address,
                ],
                'game_details' => [
                    'date'         => $gameSlot->game_date->toDateString(),
                    'start_time'   => $gameSlot->start_time,
                    'end_time'     => $gameSlot->end_time,
                    'court_name'   => $gameSlot->court_name,
                    'court_number' => $gameSlot->court_number,
                    'status'       => $gameSlot->status,
                    'is_blocked'   => (bool) $gameSlot->is_block,
                ],
                'location' => [
                    'name'      => $gameSlot->location->location_name ?? 'N/A',
                    'latitude'  => $gameSlot->location->latitude ?? null,
                    'longitude' => $gameSlot->location->longitude ?? null,
                    'address'   => $gameSlot->location->address ?? null,
                ],
                'my_position'       => $assignment->position,
                'assigned_at'       => $assignment->assigned_at->format('Y-m-d H:i:s'),
                'is_auto_assigned'  => (bool) $assignment->is_auto_assigned,
                'assigned_referees' => [
                    'total'    => count($allReferees),
                    'referees' => $allReferees,
                ],
            ];
        })->unique('game_slot_id')->values();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Your assigned game slots retrieved successfully.',
            'data'    => [
                'total_assignments' => $gameSlots->count(),
                'game_slots'        => $gameSlots,
            ],
        ];
    }

    /**
     * Get details of a specific game slot.
     *
     * @param  User  $referee
     * @param  int|string  $gameSlotId
     * @return array
     */
    public function getGameSlotDetails(User $referee, $gameSlotId): array
    {
        if (!$referee->hasRole('referee')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only referees can access this endpoint.',
                'data'    => null,
            ];
        }

        $gameSlot = GameSlot::with([
            'location',
            'schedule.camp',
            'slotAssignments.assignable',
        ])->find($gameSlotId);

        if (!$gameSlot) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Game slot not found.',
                'data'    => null,
            ];
        }

        $isAssigned = GameSlotAssignment::where('game_slot_id', $gameSlotId)
            ->where('assignable_id', $referee->id)
            ->where('assignable_type', User::class)
            ->exists();

        if (!$isAssigned) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'You are not assigned to this game slot.',
                'data'    => null,
            ];
        }

        $camp = $gameSlot->schedule->camp;

        $crewAssignments = $gameSlot->slotAssignments->where('assignment_type', 'crew');
        $individualAssignments = $gameSlot->slotAssignments->where('assignment_type', 'individual');

        $crewMembers = [];
        if ($crewAssignments->isNotEmpty()) {
            foreach ($crewAssignments as $crewAssignment) {
                $crew = $crewAssignment->assignable;
                if ($crew && $crew->members) {
                    foreach ($crew->members as $member) {
                        $crewMembers[] = [
                            'id'        => $member->id,
                            'name'      => $member->first_name . ' ' . $member->last_name,
                            'avatar'    => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                            'email'     => $member->email,
                            'phone'     => $member->phone,
                            'type'      => 'crew_member',
                            'crew_name' => $crew->crew_name ?? 'N/A',
                            'position'  => $crewAssignment->position,
                        ];
                    }
                }
            }
        }

        $individualReferees = $individualAssignments->map(function ($individualAssignment) use ($referee) {
            $assignedReferee = $individualAssignment->assignable;

            return [
                'id'       => $assignedReferee->id,
                'name'     => $assignedReferee->first_name . ' ' . $assignedReferee->last_name,
                'avatar'   => $assignedReferee->avatar ? asset($assignedReferee->avatar) : asset('default/profile.jpg'),
                'email'    => $assignedReferee->email,
                'phone'    => $assignedReferee->phone,
                'type'     => 'individual',
                'position' => $individualAssignment->position,
                'is_me'    => $assignedReferee->id === $referee->id,
            ];
        })->values()->toArray();

        $allReferees = array_merge($crewMembers, $individualReferees);

        $response = [
            'game_slot' => [
                'id'           => $gameSlot->id,
                'date'         => $gameSlot->game_date->toDateString(),
                'start_time'   => $gameSlot->start_time,
                'end_time'     => $gameSlot->end_time,
                'court_name'   => $gameSlot->court_name,
                'court_number' => $gameSlot->court_number,
                'status'       => $gameSlot->status,
                'is_blocked'   => (bool) $gameSlot->is_block,
            ],
            'camp' => [
                'id'       => $camp->id,
                'name'     => $camp->camp_name,
                'logo'     => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                'location' => $camp->location,
                'details'  => $camp->camp_details,
                'address'  => $camp->address,
            ],
            'location' => [
                'name'      => $gameSlot->location->location_name ?? 'N/A',
                'latitude'  => $gameSlot->location->latitude ?? null,
                'longitude' => $gameSlot->location->longitude ?? null,
                'address'   => $gameSlot->location->address ?? null,
            ],
            'assigned_referees' => [
                'total'    => count($allReferees),
                'referees' => $allReferees,
            ],
        ];

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Game slot details retrieved successfully.',
            'data'    => $response,
        ];
    }

    /**
     * Get upcoming game slots for the referee.
     *
     * @param  User  $referee
     * @return array
     */
    public function getUpcomingSlots(User $referee): array
    {
        if (!$referee->hasRole('referee')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only referees can access this endpoint.',
                'data'    => null,
            ];
        }

        $today = now()->toDateString();

        $assignments = GameSlotAssignment::with([
            'gameSlot.location',
            'gameSlot.schedule.camp',
            'gameSlot.slotAssignments.assignable',
        ])
            ->where('assignable_type', User::class)
            ->where('assignable_id', $referee->id)
            ->where('assignment_type', 'individual')
            ->whereHas('gameSlot', function ($query) use ($today) {
                $query->where('game_date', '>=', $today)
                    ->where('status', 'assigned');
            })
            ->orderBy('assigned_at', 'asc')
            ->get();

        $upcomingSlots = $assignments->map(function ($assignment) {
            $gameSlot = $assignment->gameSlot;
            $camp = $gameSlot->schedule->camp;

            $allAssignments = $gameSlot->slotAssignments;
            $totalReferees = $allAssignments->where('assignment_type', 'individual')->count();

            return [
                'game_slot_id'   => $gameSlot->id,
                'camp_name'      => $camp->camp_name,
                'date'           => $gameSlot->game_date->toDateString(),
                'time'           => $gameSlot->start_time . ' - ' . $gameSlot->end_time,
                'court'          => $gameSlot->court_name,
                'location'       => $gameSlot->location->location_name ?? 'N/A',
                'my_position'    => $assignment->position,
                'total_referees' => $totalReferees,
                'address'        => $gameSlot->location->address ?? null,
            ];
        })->unique('game_slot_id')->values();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Upcoming game slots retrieved successfully.',
            'data'    => [
                'total_upcoming' => $upcomingSlots->count(),
                'slots'          => $upcomingSlots,
            ],
        ];
    }

    /**
     * Get referee's camp check-ins history.
     *
     * @param  User  $referee
     * @return array
     */
    public function getMyCheckins(User $referee): array
    {
        $checkins = CampRefereeCheckin::where('referee_id', $referee->id)
            ->with([
                'camp:id,camp_name,location,start_date,end_date,camp_logo,price',
                'payment:id,amount,paid_at',
            ])
            ->latest('checked_in_at')
            ->get();

        $formatted = $checkins->map(function ($checkin) {
            return [
                'checkin_id' => $checkin->id,
                'camp'       => [
                    'id'         => $checkin->camp->id,
                    'camp_name'  => $checkin->camp->camp_name,
                    'location'   => $checkin->camp->location,
                    'camp_logo'  => $checkin->camp->camp_logo ? asset($checkin->camp->camp_logo) : asset('default/no_image.webp'),
                    'start_date' => $checkin->camp->start_date->toDateString(),
                    'end_date'   => $checkin->camp->end_date->toDateString(),
                    'price'      => $checkin->camp->price,
                    'address'    => $checkin->camp->address,
                ],
                'payment' => $checkin->payment ? [
                    'amount'  => $checkin->payment->amount,
                    'paid_at' => $checkin->payment->paid_at->format('Y-m-d H:i:s'),
                ] : null,
                'checked_in_at' => $checkin->checked_in_at->format('Y-m-d H:i:s'),
            ];
        });

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'My check-ins fetched successfully.',
            'data'    => ['checkin_camps' => $formatted],
        ];
    }

    /**
     * Format camp data consistently.
     */
    private function formatCamp(Camp $camp): array
    {
        return [
            'id'         => $camp->id,
            'name'       => $camp->camp_name,
            'logo'       => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
            'location'   => $camp->location,
            'start_date' => $camp->start_date->toDateString(),
            'end_date'   => $camp->end_date->toDateString(),
            'timezone'   => $camp->timezone ?? null,
            'address'    => $camp->address,
        ];
    }
}
