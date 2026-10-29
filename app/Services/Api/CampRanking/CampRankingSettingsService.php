<?php

namespace App\Services\Api\CampRanking;

use App\Models\AssistantDirectorPermission;
use App\Models\CampEvaluatorRegistration;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;

class CampRankingSettingsService
{
    /**
     * Retrieve ranking settings for a camp.
     *
     * @param  User  $user
     * @param  int|string  $campId
     * @return array
     */
    public function getRankingSettings(User $user, $campId): array
    {
        if (!$user->hasRole('director')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only directors can access ranking settings.',
                'data'    => [],
            ];
        }

        $camp = Camp::find($campId);

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => [],
            ];
        }

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => null,
                    'data'    => 'Unauthorized.',
                ];
            }
        }

        $settings = [
            'camp_id'                          => $camp->id,
            'camp_name'                        => $camp->camp_name,
            'publish_for_evaluators'           => (bool) ($camp->publish_ranking_for_evaluators ?? true),
            'hide_evaluator_name'              => (bool) ($camp->hide_evaluator_name_from_referees ?? false),
            'hide_ranking_numbers'             => (bool) ($camp->hide_ranking_numbers_from_referees ?? false),
            'publish_for_referees'             => (bool) ($camp->publish_ranking_for_referees ?? false),
            'publish_evaluations_for_referees' => (bool) ($camp->publish_evaluations_for_referees ?? true),
        ];

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Ranking settings retrieved successfully.',
            'data'    => $settings,
        ];
    }

    /**
     * Update ranking settings for a camp.
     *
     * @param  User  $user
     * @param  int|string  $campId
     * @param  array  $validated
     * @return array
     */
    public function updateRankingSettings(User $user, $campId, array $validated): array
    {
        if (!$user->hasRole('director')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only directors can update ranking settings.',
                'data'    => [],
            ];
        }

        $camp = Camp::find($campId);

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => [],
            ];
        }

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => null,
                    'data'    => 'You can only manage ranking reports for your own camps.',
                ];
            }

            if (!$permission->manage_ranking_reports) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => null,
                    'data'    => 'You do not have permission to manage ranking reports for this camp.',
                ];
            }
        }

        DB::beginTransaction();
        try {
            if (isset($validated['hide_evaluator_name'])) {
                $camp->hide_evaluator_name_from_referees = $validated['hide_evaluator_name'];
            }

            if (isset($validated['hide_ranking_numbers'])) {
                $camp->hide_ranking_numbers_from_referees = $validated['hide_ranking_numbers'];
            }

            if (isset($validated['publish_for_referees'])) {
                $camp->publish_ranking_for_referees = $validated['publish_for_referees'];
            }

            if (isset($validated['publish_evaluations_for_referees'])) {
                $camp->publish_evaluations_for_referees = (bool) $validated['publish_evaluations_for_referees'];

                CampRefereeCheckin::where('camp_id', $campId)
                    ->update([
                        'can_view_evaluations' => (bool) $validated['publish_evaluations_for_referees'],
                    ]);
            }

            if (isset($validated['publish_for_evaluators'])) {
                $camp->publish_ranking_for_evaluators = $validated['publish_for_evaluators'];

                CampEvaluatorRegistration::where('camp_id', $campId)
                    ->where('status', 'approved')
                    ->update([
                        'can_view_own_evaluations' => $validated['publish_for_evaluators'],
                    ]);
            }

            $camp->save();
            DB::commit();

            $settings = [
                'camp_id'                          => $camp->id,
                'camp_name'                        => $camp->camp_name,
                'publish_for_evaluators'           => (bool) ($camp->publish_ranking_for_evaluators ?? true),
                'hide_evaluator_name'              => (bool) ($camp->hide_evaluator_name_from_referees ?? false),
                'hide_ranking_numbers'             => (bool) ($camp->hide_ranking_numbers_from_referees ?? false),
                'publish_for_referees'             => (bool) ($camp->publish_ranking_for_referees ?? false),
                'publish_evaluations_for_referees' => (bool) ($camp->publish_evaluations_for_referees ?? true),
            ];

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Ranking settings updated successfully.',
                'data'    => $settings,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update camp ranking settings: ' . $e->getMessage(), [
                'exception' => $e,
                'camp_id'   => $campId,
            ]);

            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to update settings: ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Toggle individual evaluator's permission to view evaluations.
     *
     * @param  User  $user
     * @param  int|string  $campId
     * @param  int|string  $evaluatorId
     * @param  bool  $canViewEvaluations
     * @return array
     */
    public function toggleEvaluatorPermission(User $user, $campId, $evaluatorId, bool $canViewEvaluations): array
    {
        if (!$user->hasRole('director')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only directors can manage evaluator permissions.',
                'data'    => [],
            ];
        }

        $camp = Camp::find($campId);

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => [],
            ];
        }

        if ($camp->director_id !== $user->id) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'You can only manage evaluators for your own camps.',
                'data'    => [],
            ];
        }

        $registration = CampEvaluatorRegistration::where('camp_id', $campId)
            ->where('evaluator_id', $evaluatorId)
            ->where('status', 'approved')
            ->first();

        if (!$registration) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Evaluator registration not found or not approved.',
                'data'    => [],
            ];
        }

        try {
            $registration->can_view_own_evaluations = $canViewEvaluations;
            $registration->save();

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Evaluator permission updated successfully.',
                'data'    => [
                    'evaluator_id'         => $evaluatorId,
                    'camp_id'              => $campId,
                    'can_view_evaluations' => $registration->can_view_own_evaluations,
                ],
            ];
        } catch (Exception $e) {
            Log::error('Failed to update evaluator permission: ' . $e->getMessage(), [
                'exception'    => $e,
                'camp_id'      => $campId,
                'evaluator_id' => $evaluatorId,
            ]);

            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to update permission: ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Toggle individual referee's permission to view evaluations.
     *
     * @param  User  $user
     * @param  int|string  $campId
     * @param  int|string  $refereeId
     * @param  bool  $canViewEvaluations
     * @return array
     */
    public function toggleRefereePermission(User $user, $campId, $refereeId, bool $canViewEvaluations): array
    {
        if (!$user->hasRole('director')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only directors can manage referee permissions.',
                'data'    => [],
            ];
        }

        $camp = Camp::find($campId);

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => [],
            ];
        }

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission || (! $permission->manage_ranking_reports && ! $permission->manage_roster_referees)) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You can only manage referees for your own camps.',
                    'data'    => [],
                ];
            }
        }

        $referee = User::find($refereeId);
        if (!$referee || !$referee->hasRole('referee')) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Referee not found.',
                'data'    => [],
            ];
        }

        $checkin = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->first();

        if (!$checkin) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Referee registration not found for this camp.',
                'data'    => [],
            ];
        }

        try {
            $checkin->can_view_evaluations = $canViewEvaluations;
            $checkin->save();

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Referee permission updated successfully.',
                'data'    => [
                    'referee_id'           => (int) $refereeId,
                    'camp_id'              => (int) $campId,
                    'can_view_evaluations' => (bool) $checkin->can_view_evaluations,
                ],
            ];
        } catch (Exception $e) {
            Log::error('Failed to update referee permission: ' . $e->getMessage(), [
                'exception'  => $e,
                'camp_id'    => $campId,
                'referee_id' => $refereeId,
            ]);

            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to update permission: ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }
}
