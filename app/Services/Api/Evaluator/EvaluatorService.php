<?php

namespace App\Services\Api\Evaluator;

use Modules\Director\Models\Camp;
use Modules\Director\Transformers\CampResource;

class EvaluatorService
{
    /**
     * Retrieve filtered and paginated active camps.
     *
     * @param  array  $filters
     * @return array
     */
    public function getActiveCamps(array $filters): array
    {
        $query = Camp::query()->where('status', 'active');

        if (!empty($filters['sports_type_id'])) {
            $query->where('sports_type_id', $filters['sports_type_id']);
        }

        if (!empty($filters['location'])) {
            $query->where('location', 'LIKE', "%{$filters['location']}%");
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('start_date', [$filters['start_date'], $filters['end_date']]);
        }

        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'upcoming':
                    $query->where('start_date', '>=', now()->format('Y-m-d'))
                        ->orderBy('start_date', 'asc');
                    break;
                default:
                    $query->orderBy('id', 'desc');
                    break;
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        $perPage = !empty($filters['per_page']) ? (int) $filters['per_page'] : 10;
        $camps = $query->paginate($perPage);

        return [
            'camp_list'   => CampResource::collection($camps->items()),
            'pagination' => [
                'total'        => $camps->total(),
                'per_page'     => $camps->perPage(),
                'current_page' => $camps->currentPage(),
                'last_page'    => $camps->lastPage(),
            ],
        ];
    }

    /**
     * Retrieve game overview and court slot assignments for a camp.
     *
     * @param  int|string  $campId
     * @return array
     */
    public function getGameOverview($campId): array
    {
        $camp = Camp::where('id', $campId)
            ->with([
                'schedule.locations',
                'schedule.gameSlots.slotAssignments.assignable',
            ])
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => [],
            ];
        }

        $response = [
            'camp' => [
                'camp_name' => $camp->camp_name,
                'location'  => $camp->location,
                'schedule'  => [
                    'locations' => $camp->schedule?->locations->map(function ($location) {
                        return [
                            'id'        => $location->id,
                            'name'      => $location->location_name,
                            'latitude'  => $location->latitude,
                            'longitude' => $location->longitude,
                            'address'   => $location->address,
                        ];
                    }) ?? collect(),
                    'game_courts' => [
                        'court_count' => $camp->schedule?->gameSlots->count() ?? 0,
                        'game_court'  => $camp->schedule?->gameSlots->map(function ($gameSlot) {
                            return [
                                'id'         => $gameSlot->id,
                                'court_name' => $gameSlot->court_name,
                                'assignments' => $gameSlot->slotAssignments->map(function ($assignment) {
                                    if ($assignment->assignment_type === 'crew') {
                                        return [
                                            'type'             => 'crew',
                                            'crew_id'          => $assignment->assignable_id,
                                            'crew_name'        => $assignment->assignable->crew_name ?? 'N/A',
                                            'position'         => $assignment->position,
                                            'is_auto_assigned' => $assignment->is_auto_assigned,
                                        ];
                                    }

                                    $referee = $assignment->assignable;
                                    return [
                                        'type'       => 'individual',
                                        'referee_id' => $referee->id ?? null,
                                        'name'       => $referee ? trim(($referee->first_name ?? '') . ' ' . ($referee->last_name ?? '')) : 'N/A',
                                    ];
                                }),
                            ];
                        }) ?? collect(),
                    ],
                ],
            ],
        ];

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Game overview fetched successfully.',
            'data'    => $response,
        ];
    }
}
