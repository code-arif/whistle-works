<?php

namespace App\Services\Api\Referee;

use App\Models\User;
use Modules\Director\Models\Camp;
use Modules\Director\Models\Crew;
use Modules\Director\Models\CrewMember;
use Modules\Director\Models\GameSlotAssignment;

class RefereeAssignmentCrewService
{
    /**
     * Get all crews where the referee is a member (across all camps).
     *
     * @param  User  $referee
     * @return array
     */
    public function getMyCrews(User $referee): array
    {
        if (!$referee->hasRole('referee')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only referees can access this endpoint.',
                'data'    => null,
            ];
        }

        $crewMemberships = CrewMember::with([
            'crew.camp' => function ($q) {
                $q->select('id', 'camp_name', 'camp_logo', 'location', 'start_date', 'end_date', 'status');
            },
            'crew.members',
        ])
            ->where('referee_id', $referee->id)
            ->get();

        if ($crewMemberships->isEmpty()) {
            return [
                'success' => true,
                'code'    => 200,
                'message' => 'You are not a member of any crew yet.',
                'data'    => [
                    'total_crews' => 0,
                    'crews'       => [],
                ],
            ];
        }

        $crews = $crewMemberships->map(function ($membership) use ($referee) {
            $crew = $membership->crew;
            if (!$crew || !$crew->camp) {
                return null;
            }

            $camp = $crew->camp;

            $crewAssignments = GameSlotAssignment::with('gameSlot')
                ->where('assignable_type', Crew::class)
                ->where('assignable_id', $crew->id)
                ->where('assignment_type', 'crew')
                ->get();

            $upcomingGames = $crewAssignments->filter(function ($assignment) {
                return $assignment->gameSlot
                    && $assignment->gameSlot->game_date >= now()->toDateString()
                    && $assignment->gameSlot->status === 'assigned';
            })->count();

            $completedGames = $crewAssignments->filter(function ($assignment) {
                return $assignment->gameSlot && $assignment->gameSlot->status === 'completed';
            })->count();

            $members = $crew->members->map(function ($member) use ($referee) {
                if (!$member) {
                    return null;
                }

                return [
                    'id'        => $member->id,
                    'name'      => $member->first_name . ' ' . $member->last_name,
                    'email'     => $member->email,
                    'phone'     => $member->phone ?? null,
                    'avatar'    => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                    'position'  => $member->pivot->position ?? null,
                    'joined_at' => $member->pivot->joined_at ?? null,
                    'is_me'     => $member->id === $referee->id,
                ];
            })->filter()->values();

            return [
                'crew_id'          => $crew->id,
                'crew_name'        => $crew->name,
                'crew_description' => $crew->description ?? null,
                'crew_status'      => $crew->status,
                'joined_at'        => $membership->joined_at,
                'camp'             => [
                    'id'         => $camp->id,
                    'name'       => $camp->camp_name,
                    'logo'       => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                    'location'   => $camp->location,
                    'address'    => $camp->address,
                    'start_date' => $camp->start_date,
                    'end_date'   => $camp->end_date,
                    'status'     => $camp->status,
                ],
                'statistics' => [
                    'total_members'        => $crew->members->count(),
                    'total_games_assigned' => $crewAssignments->count(),
                    'upcoming_games'       => $upcomingGames,
                    'completed_games'      => $completedGames,
                ],
                'members' => $members,
            ];
        })->filter()->values();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Your crew memberships retrieved successfully.',
            'data'    => [
                'total_crews' => $crews->count(),
                'crews'       => $crews,
            ],
        ];
    }

    /**
     * Get crews for a specific camp where the referee is a member.
     *
     * @param  User  $referee
     * @param  int|string  $campId
     * @return array
     */
    public function getCampCrews(User $referee, $campId): array
    {
        if (!$referee->hasRole('referee')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only referees can access this endpoint.',
                'data'    => null,
            ];
        }

        $camp = Camp::find($campId);
        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        $crewMemberships = CrewMember::with([
            'crew.members.referee' => function ($q) {
                $q->select('id', 'first_name', 'last_name', 'email', 'phone', 'avatar');
            },
        ])
            ->where('referee_id', $referee->id)
            ->whereHas('crew', function ($q) use ($campId) {
                $q->where('camp_id', $campId);
            })
            ->get();

        if ($crewMemberships->isEmpty()) {
            return [
                'success' => true,
                'code'    => 200,
                'message' => 'You are not a member of any crew in this camp.',
                'data'    => [
                    'camp' => [
                        'id'         => $camp->id,
                        'name'       => $camp->camp_name,
                        'logo'       => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                        'location'   => $camp->location,
                        'start_date' => $camp->start_date,
                        'end_date'   => $camp->end_date,
                        'address'    => $camp->address,
                    ],
                    'total_crews' => 0,
                    'crews'       => [],
                ],
            ];
        }

        $crews = $crewMemberships->map(function ($membership) use ($referee, $campId) {
            $crew = $membership->crew;

            $crewAssignments = GameSlotAssignment::with([
                'gameSlot.location',
                'gameSlot.schedule',
            ])
                ->where('assignable_type', Crew::class)
                ->where('assignable_id', $crew->id)
                ->where('assignment_type', 'crew')
                ->whereHas('gameSlot.schedule', function ($q) use ($campId) {
                    $q->where('camp_id', $campId);
                })
                ->get();

            $gameSlots = $crewAssignments->map(function ($assignment) {
                $slot = $assignment->gameSlot;
                return [
                    'game_slot_id'  => $slot->id,
                    'date'          => $slot->game_date,
                    'start_time'    => $slot->start_time,
                    'end_time'      => $slot->end_time,
                    'court_name'    => $slot->court_name,
                    'court_number'  => $slot->court_number,
                    'location_name' => $slot->location->location_name ?? 'N/A',
                    'address'       => $slot->location->address ?? null,
                    'status'        => $slot->status,
                    'is_blocked'    => (bool) $slot->is_block,
                    'assigned_at'   => $assignment->assigned_at->format('Y-m-d H:i:s'),
                ];
            })->values();

            $upcomingGames = $crewAssignments->filter(function ($assignment) {
                return $assignment->gameSlot->game_date >= now()->toDateString()
                    && $assignment->gameSlot->status === 'assigned';
            })->count();

            $completedGames = $crewAssignments->filter(function ($assignment) {
                return $assignment->gameSlot->status === 'completed';
            })->count();

            $members = $crew->members->map(function ($member) use ($referee) {
                if (!$member) {
                    return null;
                }

                return [
                    'id'        => $member->id,
                    'name'      => $member->first_name . ' ' . $member->last_name,
                    'email'     => $member->email,
                    'phone'     => $member->phone ?? null,
                    'avatar'    => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                    'position'  => $member->pivot->position ?? null,
                    'joined_at' => $member->pivot->joined_at ?? null,
                    'is_me'     => $member->id === $referee->id,
                ];
            })->filter()->values();

            return [
                'crew_id'             => $crew->id,
                'crew_name'           => $crew->name,
                'crew_description'    => $crew->description,
                'crew_status'         => $crew->status,
                'joined_at'           => $membership->joined_at,
                'statistics'          => [
                    'total_members'        => $crew->members->count(),
                    'total_games_assigned' => $crewAssignments->count(),
                    'upcoming_games'       => $upcomingGames,
                    'completed_games'      => $completedGames,
                ],
                'members'             => $members,
                'assigned_game_slots' => $gameSlots,
            ];
        })->values();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Your crew memberships for this camp retrieved successfully.',
            'data'    => [
                'camp' => [
                    'id'         => $camp->id,
                    'name'       => $camp->camp_name,
                    'logo'       => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                    'location'   => $camp->location,
                    'start_date' => $camp->start_date,
                    'end_date'   => $camp->end_date,
                    'address'    => $camp->address,
                ],
                'total_crews' => $crews->count(),
                'crews'       => $crews,
            ],
        ];
    }

    /**
     * Get specific crew details with all game assignments.
     *
     * @param  User  $referee
     * @param  int|string  $crewId
     * @return array
     */
    public function getCrewDetails(User $referee, $crewId): array
    {
        if (!$referee->hasRole('referee')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only referees can access this endpoint.',
                'data'    => null,
            ];
        }

        $crew = Crew::with(['camp', 'members'])->find($crewId);

        if (!$crew) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Crew not found.',
                'data'    => null,
            ];
        }

        $isMember = $crew->members->contains('id', $referee->id);
        if (!$isMember) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'You are not a member of this crew.',
                'data'    => null,
            ];
        }

        $crewAssignments = GameSlotAssignment::with([
            'gameSlot.location',
            'gameSlot.schedule',
        ])
            ->where('assignable_type', Crew::class)
            ->where('assignable_id', $crew->id)
            ->where('assignment_type', 'crew')
            ->orderBy('assigned_at', 'desc')
            ->get();

        $gameSlotsByDate = $crewAssignments->groupBy(function ($assignment) {
            return $assignment->gameSlot->game_date;
        })->map(function ($dateAssignments, $date) {
            return [
                'date'           => $date,
                'formatted_date' => \Carbon\Carbon::parse($date)->format('F d, Y'),
                'day'            => \Carbon\Carbon::parse($date)->format('l'),
                'games'          => $dateAssignments->map(function ($assignment) {
                    $slot = $assignment->gameSlot;
                    return [
                        'game_slot_id'  => $slot->id,
                        'start_time'    => $slot->start_time,
                        'end_time'      => $slot->end_time,
                        'court_name'    => $slot->court_name,
                        'court_number'  => $slot->court_number,
                        'location_name' => $slot->location->location_name ?? 'N/A',
                        'address'       => $slot->location->address ?? null,
                        'status'        => $slot->status,
                        'assigned_at'   => $assignment->assigned_at->format('Y-m-d H:i:s'),
                    ];
                })->values(),
            ];
        })->values();

        $upcomingGames = $crewAssignments->filter(function ($assignment) {
            return $assignment->gameSlot->game_date >= now()->toDateString()
                && $assignment->gameSlot->status === 'assigned';
        })->count();

        $completedGames = $crewAssignments->filter(function ($assignment) {
            return $assignment->gameSlot->status === 'completed';
        })->count();

        $members = $crew->members->map(function ($member) use ($referee) {
            return [
                'id'        => $member->id,
                'name'      => $member->first_name . ' ' . $member->last_name,
                'email'     => $member->email,
                'phone'     => $member->phone,
                'avatar'    => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                'position'  => $member->pivot->position ?? null,
                'joined_at' => $member->pivot->joined_at ?? null,
                'is_me'     => $member->id === $referee->id,
            ];
        })->values();

        $camp = $crew->camp;

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Crew details retrieved successfully.',
            'data'    => [
                'crew' => [
                    'id'          => $crew->id,
                    'name'        => $crew->name,
                    'description' => $crew->description,
                    'status'      => $crew->status,
                    'created_at'  => $crew->created_at->format('Y-m-d H:i:s'),
                ],
                'camp' => [
                    'id'         => $camp->id,
                    'name'       => $camp->camp_name,
                    'logo'       => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                    'location'   => $camp->location,
                    'address'    => $camp->address,
                    'start_date' => $camp->start_date,
                    'end_date'   => $camp->end_date,
                ],
                'statistics' => [
                    'total_members'        => $crew->members->count(),
                    'total_games_assigned' => $crewAssignments->count(),
                    'upcoming_games'       => $upcomingGames,
                    'completed_games'      => $completedGames,
                ],
                'members'            => $members,
                'game_slots_by_date' => $gameSlotsByDate,
            ],
        ];
    }

    /**
     * Get upcoming games for all crews where referee is a member.
     *
     * @param  User  $referee
     * @return array
     */
    public function getMyCrewUpcomingGames(User $referee): array
    {
        if (!$referee->hasRole('referee')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only referees can access this endpoint.',
                'data'    => null,
            ];
        }

        $crewIds = CrewMember::where('referee_id', $referee->id)->pluck('crew_id');

        if ($crewIds->isEmpty()) {
            return [
                'success' => true,
                'code'    => 200,
                'message' => 'You are not a member of any crew.',
                'data'    => [
                    'total_upcoming_games' => 0,
                    'games'                => [],
                ],
            ];
        }

        $today = now()->toDateString();

        $upcomingAssignments = GameSlotAssignment::with([
            'gameSlot.location',
            'gameSlot.schedule.camp',
            'assignable',
        ])
            ->where('assignment_type', 'crew')
            ->where('assignable_type', Crew::class)
            ->whereIn('assignable_id', $crewIds)
            ->whereHas('gameSlot', function ($q) use ($today) {
                $q->where('game_date', '>=', $today)
                    ->where('status', 'assigned');
            })
            ->orderBy('assigned_at', 'asc')
            ->get();

        $games = $upcomingAssignments->map(function ($assignment) {
            $slot = $assignment->gameSlot;
            $camp = $slot->schedule->camp;
            $crew = $assignment->assignable;

            return [
                'game_slot_id'   => $slot->id,
                'date'           => $slot->game_date,
                'formatted_date' => \Carbon\Carbon::parse($slot->game_date)->format('F d, Y'),
                'day'            => \Carbon\Carbon::parse($slot->game_date)->format('l'),
                'start_time'     => $slot->start_time,
                'end_time'       => $slot->end_time,
                'court_name'     => $slot->court_name,
                'court_number'   => $slot->court_number,
                'location_name'  => $slot->location->location_name ?? 'N/A',
                'address'        => $slot->location->address ?? null,
                'crew'           => [
                    'id'           => $crew->id,
                    'name'         => $crew->name,
                    'member_count' => $crew->members()->count(),
                ],
                'camp'           => [
                    'id'       => $camp->id,
                    'name'     => $camp->camp_name,
                    'location' => $camp->location,
                    'address'  => $camp->address ?? null,
                ],
            ];
        })->values();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Upcoming crew games retrieved successfully.',
            'data'    => [
                'total_upcoming_games' => $games->count(),
                'games'                => $games,
            ],
        ];
    }
}
