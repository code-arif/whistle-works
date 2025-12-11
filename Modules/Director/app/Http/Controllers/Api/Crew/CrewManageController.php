<?php

namespace Modules\Director\Http\Controllers\Api\Crew;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\Director\Transformers\Referee\AvailableRefereeResource;
use Modules\Director\Models\{Camp, Crew, CrewMember, CampRefereeCheckin, GameSlot, RefereeAssignment};

class CrewManageController extends Controller
{
    use ApiResponse;

    /**
     * Create a new crew
     */
    public function createCrew(Request $request, $campId)
    {
        $user = auth('api')->user();

        // Validate
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500'
        ]);

        // Verify camp ownership
        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        // Check for duplicate crew name in same camp
        $exists = Crew::where('camp_id', $campId)
            ->where('name', $request->name)
            ->exists();

        if ($exists) {
            return $this->error('Crew with this name already exists in this camp.', null, 400);
        }

        // Create crew
        $crew = Crew::create([
            'camp_id' => $campId,
            'name' => $request->name,
            'description' => $request->description,
            'status' => 'active'
        ]);

        return $this->success(
            'Crew created successfully.',
            [
                'crew' => $crew,
                'member_count' => 0
            ],
            201
        );
    }

    /**
     * Get all crews for a camp
     */
    public function getCrews($campId)
    {
        $user = auth('api')->user();

        // Verify camp ownership
        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $crews = Crew::where('camp_id', $campId)
            ->withCount('members')
            ->with(['members' => function ($query) {
                $query->select('users.id', 'users.first_name', 'users.last_name', 'users.email');
            }])
            ->get();

        $formatted = $crews->map(function ($crew) {
            return [
                'id' => $crew->id,
                'name' => $crew->name,
                'description' => $crew->description,
                'status' => $crew->status,
                'member_count' => $crew->members_count,
                'members' => $crew->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->first_name . ' ' . $member->last_name,
                        'email' => $member->email
                    ];
                }),
                'created_at' => $crew->created_at->format('Y-m-d H:i:s')
            ];
        });

        return $this->success(
            'Crews fetched successfully.',
            [
                'total_crews' => $formatted->count(),
                'crews' => $formatted
            ],
            200
        );
    }

    /**
     * Get crew details
     */
    public function getCrewDetails($crewId)
    {
        $user = auth('api')->user();

        $crew = Crew::with(['camp', 'members', 'gameSlots'])
            ->withCount('members')
            ->find($crewId);

        if (!$crew) {
            return $this->error('Crew not found.', null, 404);
        }

        // Verify ownership
        if ($crew->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        return $this->success(
            'Crew details fetched successfully.',
            [
                'id' => $crew->id,
                'name' => $crew->name,
                'description' => $crew->description,
                'status' => $crew->status,
                'member_count' => $crew->members_count,
                'members' => $crew->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'joined_at' => $member->pivot->joined_at
                    ];
                }),
                'assigned_games' => $crew->gameSlots->count(),
                'camp' => [
                    'id' => $crew->camp->id,
                    'name' => $crew->camp->camp_name
                ]
            ],
            200
        );
    }

    /**
     * Add referees to crew
     */
    public function addMembers(Request $request, $crewId)
    {
        $user = auth('api')->user();

        $request->validate([
            'referee_ids' => 'required|array|min:1|max:5',
            'referee_ids.*' => 'exists:users,id'
        ]);

        $crew = Crew::with('camp', 'members')->find($crewId);

        if (!$crew) {
            return $this->error('Crew not found.', null, 404);
        }

        // Verify ownership
        if ($crew->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        $refereeIds = $request->referee_ids;

        // --- Capacity Check ---
        if ($crew->members->count() + count($refereeIds) > 5) {
            return $this->error(
                [
                    'current_members' => $crew->members->count(),
                    'trying_to_add' => count($refereeIds)
                ],
                'Crew can have max 5 members.',
                400
            );
        }

        $added = [];
        $skipped = [];

        foreach ($refereeIds as $refereeId) {

            // Check if checked in
            $isCheckedIn = CampRefereeCheckin::where('camp_id', $crew->camp_id)
                ->where('referee_id', $refereeId)
                ->exists();

            if (!$isCheckedIn) {
                $skipped[] = [
                    'referee_id' => $refereeId,
                    'reason' => 'Not checked in'
                ];
                continue;
            }

            // Check if already in ANY crew for this camp
            $existing = CrewMember::whereHas('crew', function ($q) use ($crew) {
                $q->where('camp_id', $crew->camp_id);
            })
                ->where('referee_id', $refereeId)
                ->first();

            if ($existing) {
                $skipped[] = [
                    'referee_id' => $refereeId,
                    'reason' => 'Already in another crew'
                ];
                continue;
            }

            // Add to crew
            $crew_member = CrewMember::create([
                'crew_id' => $crewId,
                'referee_id' => $refereeId,
                'joined_at' => now()
            ]);

            $added[] = $refereeId;
        }

        $crew->load(['members' => function ($q) {
            $q->with('referee:id,first_name,last_name,email');
        }]);

        return $this->success(
            'Members processed.',
            [
                'crew' => [
                    'id' => $crew->id,
                    'name' => $crew->name,
                    'member_count' => $crew->members->count(),
                    'members' => $crew->members->map(function ($member) {
                        return [
                            'id' => $member->id,
                            'name' => $member->first_name . ' ' . $member->last_name,
                            'email' => $member->email,
                            'joined_at' => $member->pivot->joined_at
                        ];
                    })
                ],
                'added' => $added,
                'skipped' => $skipped
            ],
            201
        );
    }


    /**
     * Remove referees from crew
     */
    public function removeMembers(Request $request, $crewId)
    {
        $user = auth('api')->user();

        $request->validate([
            'referee_ids' => 'required|array|min:1',
            'referee_ids.*' => 'exists:users,id',
        ]);

        $crew = Crew::with('camp')->find($crewId);

        if (!$crew) {
            return $this->error('Crew not found.', null, 404);
        }

        // Verify ownership
        if ($crew->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        $refereeIds = $request->referee_ids;

        $removed = [];
        $notFound = [];

        foreach ($refereeIds as $refereeId) {
            $member = CrewMember::where('crew_id', $crewId)
                ->where('referee_id', $refereeId)
                ->first();

            if (!$member) {
                $notFound[] = $refereeId;
                continue;
            }

            $member->delete();
            $removed[] = $refereeId;
        }

        return $this->success(
            'Members removed successfully.',
            [
                'crew_id' => $crewId,
                'removed' => $removed,
                'not_found_in_crew' => $notFound
            ],
            200
        );
    }


    /**
     * Update crew details
     */
    public function updateCrew(Request $request, $crewId)
    {
        $user = auth('api')->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
            'status' => 'sometimes|in:active,inactive'
        ]);

        $crew = Crew::with('camp')->find($crewId);

        if (!$crew) {
            return $this->error('Crew not found.', null, 404);
        }

        // Verify ownership
        if ($crew->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        // Check for duplicate name if updating name
        if ($request->filled('name') && $request->name !== $crew->name) {
            $exists = Crew::where('camp_id', $crew->camp_id)
                ->where('name', $request->name)
                ->where('id', '!=', $crewId)
                ->exists();

            if ($exists) {
                return $this->error('Crew with this name already exists in this camp.', null, 400);
            }
        }

        // Update fields
        if ($request->filled('name')) {
            $crew->name = $request->name;
        }
        if ($request->has('description')) {
            $crew->description = $request->description;
        }
        if ($request->filled('status')) {
            $crew->status = $request->status;
        }

        $crew->save();

        return $this->success(
            'Crew updated successfully.',
            $crew,
            200
        );
    }

    /**
     * Delete crew
     */
    public function deleteCrew($crewId)
    {
        $user = auth('api')->user();

        $crew = Crew::with('camp')->find($crewId);

        if (!$crew) {
            return $this->error('Crew not found.', null, 404);
        }

        // Verify ownership
        if ($crew->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        // Check if crew is assigned to any game slots
        $assignedGames = $crew->gameSlots()->count();
        if ($assignedGames > 0) {
            return $this->error(
                "Cannot delete crew. It is assigned to {$assignedGames} game slot(s). Remove assignments first.",
                ['assigned_games_count' => $assignedGames],
                400
            );
        }

        $crew->delete();

        return $this->success('Crew deleted successfully.', null, 200);
    }

    /**
     * Assign crew to game slot
     */
    public function assignCrewToSlot(Request $request, $gameSlotId)
    {
        $user = auth('api')->user();

        $request->validate([
            'crew_id' => 'required|exists:crews,id'
        ]);

        $gameSlot = GameSlot::with('schedule.camp')->find($gameSlotId);

        if (!$gameSlot) {
            return $this->error('Game slot not found.', null, 404);
        }

        // Verify ownership
        if ($gameSlot->schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        $crew = Crew::with('members')->find($request->crew_id);

        // Verify crew belongs to same camp
        if ($crew->camp_id !== $gameSlot->schedule->camp_id) {
            return $this->error('Crew does not belong to this camp.', null, 400);
        }

        // Check if crew has members
        if ($crew->members->isEmpty()) {
            return $this->error('Crew has no members. Add members before assigning.', null, 400);
        }

        DB::beginTransaction();
        try {
            // Remove existing individual assignments if any
            RefereeAssignment::where('game_slot_id', $gameSlotId)->delete();

            // Update game slot
            $gameSlot->update([
                'crew_id' => $crew->id,
                'assignment_mode' => 'crew',
                'status' => 'assigned'
            ]);

            // Create referee assignments for all crew members
            foreach ($crew->members as $member) {
                RefereeAssignment::create([
                    'game_slot_id' => $gameSlotId,
                    'referee_id' => $member->id,
                    'crew_id' => $crew->id,
                    'assignment_type' => 'manual'
                ]);
            }

            DB::commit();

            $gameSlot->load('crew', 'refereeAssignments.referee');

            return $this->success(
                'Crew assigned to game slot successfully.',
                [
                    'game_slot' => $gameSlot,
                    'assigned_referees_count' => $crew->members->count()
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to assign crew: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove crew assignment from game slot
     */
    public function removeCrewFromSlot($gameSlotId)
    {
        $user = auth('api')->user();

        $gameSlot = GameSlot::with('schedule.camp')->find($gameSlotId);

        if (!$gameSlot) {
            return $this->error('Game slot not found.', null, 404);
        }

        // Verify ownership
        if ($gameSlot->schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        if (!$gameSlot->isCrewAssignment()) {
            return $this->error('This game slot is not assigned to a crew.', null, 400);
        }

        DB::beginTransaction();
        try {
            // Remove all referee assignments
            RefereeAssignment::where('game_slot_id', $gameSlotId)->delete();

            // Update game slot
            $gameSlot->update([
                'crew_id' => null,
                'assignment_mode' => 'individual',
                'status' => 'available'
            ]);

            DB::commit();

            return $this->success('Crew removed from game slot successfully.', null, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to remove crew: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get available referees for crew (not in any crew for this camp)
     */
    public function getAvailableReferees($campId)
    {
        $user = auth('api')->user();

        // Verify camp ownership
        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        // Get all checked-in referee IDs
        $checkedInRefereeIds = CampRefereeCheckin::where('camp_id', $campId)
            ->pluck('referee_id');

        // Get referee IDs already assigned to any crew in this camp
        $assignedRefereeIds = CrewMember::whereHas('crew', function ($q) use ($campId) {
            $q->where('camp_id', $campId);
        })->pluck('referee_id');

        // Available = checked-in but not assigned
        $availableRefereeIds = $checkedInRefereeIds->diff($assignedRefereeIds);

        $perPage = request()->get('per_page', 15); // default 15

        $availableReferees = User::whereIn('id', $availableRefereeIds)
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'avatar')
            ->orderBy('first_name')
            ->paginate($perPage);

        return $this->success('Available referees fetched successfully.', [
            'total_checked_in' => $checkedInRefereeIds->count(),
            'in_crews'         => $assignedRefereeIds->count(),
            'available'        => $availableReferees->total(),
            'referees' => AvailableRefereeResource::collection($availableReferees),
            'pagination'       => [
                'total'         => $availableReferees->total(),
                'per_page'      => $availableReferees->perPage(),
                'current_page'  => $availableReferees->currentPage(),
                'last_page'     => $availableReferees->lastPage(),
            ],
        ], 200);
    }

    /**
     * Bulk add members to crew
     */
    public function bulkAddMembers(Request $request, $crewId)
    {
        $user = auth('api')->user();

        $request->validate([
            'referee_ids' => 'required|array|min:1',
            'referee_ids.*' => 'exists:users,id'
        ]);

        $crew = Crew::with('camp')->find($crewId);

        if (!$crew) {
            return $this->error('Crew not found.', null, 404);
        }

        // Verify ownership
        if ($crew->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        $added = [];
        $skipped = [];
        $errors = [];

        foreach ($request->referee_ids as $refereeId) {
            // Check if checked in
            $isCheckedIn = CampRefereeCheckin::where('camp_id', $crew->camp_id)
                ->where('referee_id', $refereeId)
                ->exists();

            if (!$isCheckedIn) {
                $errors[] = [
                    'referee_id' => $refereeId,
                    'reason' => 'Not checked in to camp'
                ];
                continue;
            }

            // Check if already in a crew for this camp
            $existingMembership = CrewMember::whereHas('crew', function ($query) use ($crew) {
                $query->where('camp_id', $crew->camp_id);
            })
                ->where('referee_id', $refereeId)
                ->exists();

            if ($existingMembership) {
                $skipped[] = $refereeId;
                continue;
            }

            // Add to crew
            CrewMember::create([
                'crew_id' => $crewId,
                'referee_id' => $refereeId,
                'joined_at' => now()
            ]);

            $added[] = $refereeId;
        }

        return $this->success(
            'Bulk add members completed.',
            [
                'added_count' => count($added),
                'skipped_count' => count($skipped),
                'error_count' => count($errors),
                'added_ids' => $added,
                'skipped_ids' => $skipped,
                'errors' => $errors
            ],
            200
        );
    }
}
