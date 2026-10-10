<?php

namespace Modules\Director\app\Services\Ai;

use App\Models\CampEvaluatorRegistration;
use App\Models\CampRefereeJearsyNumber;
use App\Models\RefereeEvaluation;
use App\Models\User;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;

class AiDirectoryService
{
    /**
     * Retrieve paginated camps directory for the authenticated director.
     * Includes all active, upcoming, and previous camps with search support.
     *
     * @param User $director Authenticated director
     * @param array $params Query parameters (search, per_page, page)
     * @return array
     */
    public function getCamps(User $director, array $params = []): array
    {
        $perPage = max(1, min(100, (int) ($params['per_page'] ?? 15)));
        $page = max(1, (int) ($params['page'] ?? 1));
        $search = trim($params['search'] ?? '');

        $query = Camp::forDirectorOrAssistant($director->id)
            ->select(['id', 'director_id', 'camp_name', 'camp_logo', 'start_date', 'end_date']);

        if ($search !== '') {
            $query->where('camp_name', 'LIKE', "%{$search}%");
        }

        $query->orderBy('start_date', 'desc');

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $camps = collect($paginated->items())->map(function ($camp) {
            $logoUrl = null;
            if (!empty($camp->camp_logo)) {
                $logoUrl = filter_var($camp->camp_logo, FILTER_VALIDATE_URL)
                    ? $camp->camp_logo
                    : asset($camp->camp_logo);
            } else {
                $logoUrl = asset('default/no_image.webp');
            }

            return [
                'id' => (int) $camp->id,
                'name' => (string) ($camp->camp_name ?? "Camp #{$camp->id}"),
                'logo' => $logoUrl,
            ];
        })->values()->all();

        return [
            'data' => $camps,
            'pagination' => [
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
            ],
        ];
    }

    /**
     * Retrieve paginated participants (referees/evaluators) for AI mention tagging.
     * Supports filtering by specific camp or across all camps of the director,
     * role filtering (referee, evaluator, all), and name searching.
     *
     * @param User $director Authenticated director
     * @param array $params Query parameters (camp_id, role, search, per_page, page)
     * @return array
     */
    public function getParticipants(User $director, array $params = []): array
    {
        $perPage = max(1, min(100, (int) ($params['per_page'] ?? 20)));
        $page = max(1, (int) ($params['page'] ?? 1));
        $campId = !empty($params['camp_id']) ? (int) $params['camp_id'] : null;
        $role = !empty($params['role']) ? strtolower(trim($params['role'])) : null;
        $search = trim($params['search'] ?? '');

        // 1. Resolve authorized camp IDs for this director
        if ($campId) {
            $campExists = Camp::forDirectorOrAssistant($director->id)
                ->where('id', $campId)
                ->exists();

            if (!$campExists) {
                return [
                    'data' => [],
                    'pagination' => [
                        'total' => 0,
                        'per_page' => $perPage,
                        'current_page' => $page,
                        'last_page' => 1,
                    ],
                ];
            }
            $targetCampIds = [$campId];
        } else {
            $targetCampIds = Camp::forDirectorOrAssistant($director->id)
                ->pluck('id')
                ->all();

            if (empty($targetCampIds)) {
                return [
                    'data' => [],
                    'pagination' => [
                        'total' => 0,
                        'per_page' => $perPage,
                        'current_page' => $page,
                        'last_page' => 1,
                    ],
                ];
            }
        }

        // 2. Resolve Referees and Evaluators IDs
        $refereeIds = [];
        $evaluatorIds = [];

        $includeReferees = empty($role) || $role === 'all' || $role === 'referee';
        $includeEvaluators = empty($role) || $role === 'all' || $role === 'evaluator';

        if ($includeReferees) {
            $checkinRefIds = CampRefereeCheckin::whereIn('camp_id', $targetCampIds)
                ->whereIn('registration_status', ['registered', 'checked_in'])
                ->pluck('referee_id')
                ->all();

            $jerseyRefIds = CampRefereeJearsyNumber::whereIn('camp_id', $targetCampIds)
                ->pluck('referee_id')
                ->all();

            $refereeIds = array_unique(array_filter(array_merge($checkinRefIds, $jerseyRefIds)));
        }

        if ($includeEvaluators) {
            $regEvalIds = CampEvaluatorRegistration::whereIn('camp_id', $targetCampIds)
                ->where('status', '!=', 'rejected')
                ->pluck('evaluator_id')
                ->all();

            $evalRecordsIds = RefereeEvaluation::whereIn('camp_id', $targetCampIds)
                ->pluck('evaluator_id')
                ->all();

            $evaluatorIds = array_unique(array_filter(array_merge($regEvalIds, $evalRecordsIds)));
        }

        $allUserIds = array_unique(array_merge($refereeIds, $evaluatorIds));

        if (empty($allUserIds)) {
            return [
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => 1,
                ],
            ];
        }

        // 3. Query Users with search and sorting by last name
        $userQuery = User::whereIn('id', $allUserIds)
            ->select(['id', 'first_name', 'last_name', 'username', 'avatar']);

        if ($search !== '') {
            $userQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('username', 'LIKE', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // Sort alphabetically by last name (then first name)
        $userQuery->orderBy('last_name', 'asc')->orderBy('first_name', 'asc');

        $paginated = $userQuery->paginate($perPage, ['*'], 'page', $page);

        $refereeIdLookup = array_flip($refereeIds);
        $evaluatorIdLookup = array_flip($evaluatorIds);

        $participants = collect($paginated->items())->map(function ($person) use ($refereeIdLookup, $evaluatorIdLookup) {
            $fullName = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
            if ($fullName === '') {
                $fullName = $person->username ?? "User #{$person->id}";
            }

            $avatarUrl = null;
            if (!empty($person->avatar)) {
                $avatarUrl = filter_var($person->avatar, FILTER_VALIDATE_URL)
                    ? $person->avatar
                    : asset($person->avatar);
            } else {
                $avatarUrl = asset('default/profile.jpg');
            }

            $isRef = isset($refereeIdLookup[$person->id]);
            $isEval = isset($evaluatorIdLookup[$person->id]);

            $participantRole = ($isRef && $isEval) ? 'both' : ($isRef ? 'referee' : 'evaluator');

            return [
                'id' => (int) $person->id,
                'name' => $fullName,
                'avatar' => $avatarUrl,
                'role' => $participantRole,
            ];
        })->values()->all();

        return [
            'data' => $participants,
            'pagination' => [
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
            ],
        ];
    }
}
