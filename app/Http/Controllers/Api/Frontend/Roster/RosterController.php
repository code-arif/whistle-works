<?php

namespace App\Http\Controllers\Api\Frontend\Roster;

use App\Traits\ApiResponse;
use Modules\Director\Models\Camp;
use App\Http\Controllers\Controller;

class RosterController extends Controller
{
    use ApiResponse;

    /**
     * Get camp details
     */
    public function campDetails($id)
    {
        $user = auth('api')->user();

        // Camp Details Controller
        $camp = Camp::where('id', $id)
            ->with([
                'sportsType',
                'director',
                'schedule.locations',
                'schedule.gameSlots.slotAssignments.assignable',
                'checkedInReferees',
                'evaluations.evaluator'
            ])
            ->first();

        if (!$camp) {
            return $this->error([], 'Camp not found.', 404);
        }

        // Get all game slot assignments for this camp
        $gameSlotAssignments = $camp->schedule?->gameSlots->flatMap(function ($gameSlot) {
            return $gameSlot->slotAssignments;
        }) ?? collect();

        $response = [
            'camp' => [
                'camp_name' => $camp->camp_name,
                'camp_logo' => $camp->camp_logo ? asset('' . $camp->camp_logo) : asset('default/no_image.webp'),
                'location' => $camp->location,
                'price' => $camp->price,
                'sports_type' => [
                    'id' => $camp->sportsType->id,
                    'name' => $camp->sportsType->sports_name,
                    'icon' => $camp->sportsType->icon ? asset('' . $camp->sportsType->icon) : asset('default/no_image.webp')
                ],
                'director' => [
                    'id' => $camp->director->id,
                    'name' => $camp->director->first_name . ' ' . $camp->director->last_name,
                    'avatar' => $camp->director->avatar ? asset('' . $camp->director->avatar) : asset('default/profile.jpg'),
                    'email' => $camp->director->email,
                    'phone' => $camp->director->phone ?? null,
                    'address' => $camp->director->address ?? null,
                    'biography' => $camp->director->biography ?? null,
                ],
                'schedule' => [
                    'locations' => $camp->schedule?->locations->map(function ($location) {
                        return [
                            'id' => $location->id,
                            'name' => $location->location_name,
                            'latitude' => $location->latitude,
                            'longitude' => $location->longitude
                        ];
                    }) ?? collect(),
                    'game_courts' => $camp->schedule?->gameSlots->map(function ($gameSlot) {
                        return [
                            'id' => $gameSlot->id,
                            'game_date' => $gameSlot->game_date,
                            'start_time' => $gameSlot->start_time,
                            'end_time' => $gameSlot->end_time,
                            'court_name' => $gameSlot->court_name,
                            'status' => $gameSlot->status,
                            'is_block' => $gameSlot->is_block,

                            // Slot Assignments (Referees/Crew)
                            'assignments' => $gameSlot->slotAssignments->map(function ($assignment) {
                                // Check if it's crew or individual
                                if ($assignment->assignment_type === 'crew') {
                                    return [
                                        'type' => 'crew',
                                        'crew_id' => $assignment->assignable_id,
                                        'crew_name' => $assignment->assignable->name ?? 'N/A',
                                        'position' => $assignment->position,
                                        'is_auto_assigned' => $assignment->is_auto_assigned,
                                        'jourcy_number' => $assignment->jourcy_number ?? null,
                                        'phone' => $assignment->phone ?? null,
                                        'address' => $assignment->address ?? null,
                                        'biography' => $assignment->biography ?? null,
                                    ];
                                } else {
                                    // Individual referee
                                    $referee = $assignment->assignable; // This is User model
                                    return [
                                        'type' => 'individual',
                                        'referee_id' => $referee->id,
                                        'name' => $referee->first_name . ' ' . $referee->last_name,
                                        'avatar' => $referee->avatar ? asset($referee->avatar) : asset('default/profile.jpg'),
                                        'email' => $referee->email,
                                        'position' => $assignment->position,
                                        'is_auto_assigned' => $assignment->is_auto_assigned,
                                        'jourcy_number' => $assignment->jourcy_number ?? null,
                                        'phone' => $assignment->phone ?? null,
                                        'address' => $assignment->address ?? null,
                                        'biography' => $assignment->biography ?? null,
                                    ];
                                }
                            }),
                        ];
                    }) ?? collect(),
                ],
                'referees' => $camp->checkedInReferees->map(function ($referee) use ($gameSlotAssignments) {
                    // Count how many games this referee is assigned to
                    $assignedGamesCount = $gameSlotAssignments->where('assignment_type', 'individual')
                        ->where('assignable_id', $referee->referee->id)
                        ->count();

                    return [
                        'id' => $referee->referee->id,
                        'name' => $referee->referee->first_name . ' ' . $referee->referee->last_name,
                        'avatar' => $referee->referee->avatar ? asset('' . $referee->referee->avatar) : asset('default/profile.jpg'),
                        'email' => $referee->referee->email,
                        'phone' => $referee->referee->phone,
                        'address' => $referee->referee->address ?? null,
                        'jourcy_number' => $referee->referee->jourcy_number ?? null,
                        'biography' => $referee->referee->biography ?? null,
                        'status' => $referee->registration_status,
                        'assigned_games_count' => $assignedGamesCount,
                    ];
                }),
                'evaluators' => $camp->evaluations->pluck('evaluator')->unique('id')->map(function ($evaluator) {
                    return [
                        'id' => $evaluator->id,
                        'name' => $evaluator->first_name . ' ' . $evaluator->last_name,
                        'avatar' => $evaluator->avatar ? asset($evaluator->avatar) : asset('default/profile.jpg'),
                        'email' => $evaluator->email,
                        'phone' => $evaluator->phone ?? null,
                        'address' => $evaluator->address ?? null,
                        'biography' => $evaluator->biography ?? null,
                    ];
                })->values(),
            ]
        ];

        return $this->success(
            'Camp details fetched successfully.',
            $response,
            200
        );
    }
}
