<?php

namespace App\Services\Api\DirectorCampManage;

use App\Mail\AssistantDirectorAssignedMail;
use App\Models\AssistantDirectorPermission;
use App\Models\User;
use App\Notifications\AssistantDirectorAssignedNotification;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Director\Models\Camp;

class AssistantDirectorPermissionService
{
    protected array $permissionKeys = [
        'build_schedule',
        'assign_referees',
        'publish_camp',
        'manage_ranking_reports',
        'manage_roster_referees',
        'manage_roster_evaluators',
        'manage_roster_crews',
        'manage_announcements',
    ];

    /**
     * Get list of candidate assistant directors and their current permission status for a camp.
     *
     * @param  int  $authUserId
     * @param  int|null  $campId
     * @param  string|null  $search
     * @return Collection
     */
    public function getAssistantDirectorList(int $authUserId, ?int $campId, ?string $search): Collection
    {
        $directors = User::role('director')
            ->where('id', '!=', $authUserId)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'avatar')
            ->get();

        $permissions = $campId
            ? AssistantDirectorPermission::where('camp_id', $campId)->get()->keyBy('assistant_director_id')
            : collect();

        return $directors->map(function ($director) use ($permissions, $campId) {
            /** @var AssistantDirectorPermission|null $permission */
            $permission = $campId ? $permissions->get($director->id) : null;
            $isAssigned = $permission !== null;

            return [
                'id'          => $director->id,
                'first_name'  => $director->first_name,
                'last_name'   => $director->last_name,
                'email'       => $director->email,
                'phone'       => $director->phone,
                'avatar'      => $director->avatar,
                'is_assigned' => $isAssigned,
                'permissions' => $permission ? [
                    'id'                       => $permission->id,
                    'director_id'              => $permission->director_id,
                    'assistant_director_id'    => $permission->assistant_director_id,
                    'camp_id'                  => $permission->camp_id,
                    'build_schedule'           => (bool) $permission->build_schedule,
                    'assign_referees'          => (bool) $permission->assign_referees,
                    'publish_camp'             => (bool) $permission->publish_camp,
                    'manage_ranking_reports'   => (bool) $permission->manage_ranking_reports,
                    'manage_roster_referees'   => (bool) $permission->manage_roster_referees,
                    'manage_roster_evaluators' => (bool) $permission->manage_roster_evaluators,
                    'manage_roster_crews'      => (bool) $permission->manage_roster_crews,
                    'manage_announcements'     => (bool) $permission->manage_announcements,
                ] : null,
            ];
        });
    }

    /**
     * Assign or update assistant director permissions for a camp.
     *
     * @param  User  $authDirector
     * @param  array  $validated
     * @return array
     */
    public function assignOrUpdatePermissions(User $authDirector, array $validated): array
    {
        $assistantDirector = User::role('director')->where('id', $validated['assistant_director_id'])->first();

        if (!$assistantDirector) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Assistant Director not found.',
                'data'    => [],
            ];
        }

        $camp = Camp::find($validated['camp_id']);
        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => [],
            ];
        }

        // Handle permissions payload (nested under 'permissions' or top-level)
        $nestedPermissions = $validated['permissions'] ?? [];
        $permissionData = [];

        foreach ($this->permissionKeys as $key) {
            if (array_key_exists($key, $nestedPermissions)) {
                $permissionData[$key] = (bool) $nestedPermissions[$key];
            } elseif (array_key_exists($key, $validated)) {
                $permissionData[$key] = (bool) $validated[$key];
            }
        }

        // Check if this user was already assigned as an assistant director before anywhere
        $isAlreadyAssistantDirector = AssistantDirectorPermission::where('assistant_director_id', $assistantDirector->id)->exists();

        $permission = AssistantDirectorPermission::where('camp_id', $validated['camp_id'])
            ->where('assistant_director_id', $assistantDirector->id)
            ->first();

        if ($permission) {
            if (!empty($permissionData)) {
                $permission->update($permissionData);
            }
            $wasCreated = false;
        } else {
            $insertData = [
                'director_id'           => $authDirector->id,
                'assistant_director_id' => $assistantDirector->id,
                'camp_id'               => $validated['camp_id'],
            ];

            foreach ($this->permissionKeys as $key) {
                $insertData[$key] = $permissionData[$key] ?? false;
            }

            $permission = AssistantDirectorPermission::create($insertData);
            $wasCreated = true;
        }

        // 1. Send System Notification to Assistant Director
        try {
            $assistantDirector->notify(new AssistantDirectorAssignedNotification($camp, $authDirector, $assistantDirector));
        } catch (Exception $e) {
            Log::warning('Failed to send system notification: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }

        // 2. Send Mail only if assigned for the very first time
        if (!$isAlreadyAssistantDirector) {
            try {
                Mail::to($assistantDirector->email)->send(new AssistantDirectorAssignedMail($assistantDirector, $authDirector, $camp));
            } catch (Exception $e) {
                Log::warning('Failed to send assignment email to assistant director: ' . $e->getMessage(), [
                    'exception' => $e,
                ]);
            }
        }

        return [
            'success' => true,
            'code'    => $wasCreated ? 201 : 200,
            'message' => $wasCreated
                ? 'Assistant director permissions created successfully.'
                : 'Assistant director permissions updated successfully.',
            'data'    => $permission,
        ];
    }

    /**
     * Get list of camps where the logged-in user is an assistant director.
     *
     * @param  User  $user
     * @param  int  $perPage
     * @return array
     */
    public function getAssistantDirectorCampList(User $user, int $perPage = 8): array
    {
        $permittedCampIds = AssistantDirectorPermission::query()
            ->where('assistant_director_id', $user->id)
            ->pluck('camp_id');

        $camps = Camp::query()
            ->whereIn('id', $permittedCampIds)
            ->with(['sportsType', 'checkedInReferees', 'schedule', 'assistantDirectorPermissions'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $response = [
            'camp_list' => $camps->map(function ($camp) use ($user) {
                $assistantDirector = $camp->assistantDirectorPermissions->where('assistant_director_id', $user->id);

                return [
                    'camp_id'        => $camp->id,
                    'camp_name'      => $camp->camp_name,
                    'camp_logo'      => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                    'location'       => $camp->location,
                    'timezone'       => $camp->timezone,
                    'timezone_name'  => $camp->timezone_display_name,
                    'sports_type'    => $camp->sportsType->sports_name ?? null,
                    'sports_type_id' => $camp->sports_type_id,
                    'status'         => $camp->status,

                    // totals
                    'total_referees' => $camp->checkedInReferees->count(),
                    'total_courts'   => $camp->schedule ? $camp->schedule->gameSlots()->count() : 0,

                    // metadata
                    'director_id'    => $camp->director_id,
                    'created_at'     => $camp->created_at->format('Y-m-d H:i:s'),
                    'updated_at'     => $camp->updated_at->format('Y-m-d H:i:s'),

                    // Permission
                    'permissions'    => $assistantDirector,
                ];
            }),

            'pagination' => [
                'total'        => $camps->total(),
                'per_page'     => $camps->perPage(),
                'current_page' => $camps->currentPage(),
                'last_page'    => $camps->lastPage(),
            ],
        ];

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Camps fetched successfully.',
            'data'    => $response,
        ];
    }

    /**
     * Remove assistant director permission for a camp.
     *
     * @param  int  $directorId
     * @param  int  $assistantDirectorId
     * @param  int  $campId
     * @return array
     */
    public function removeCampPermission(int $directorId, int $assistantDirectorId, int $campId): array
    {
        $permittedCamp = AssistantDirectorPermission::where('director_id', $directorId)
            ->where('assistant_director_id', $assistantDirectorId)
            ->where('camp_id', $campId)
            ->first();

        if (!$permittedCamp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Permission not found.',
                'data'    => [],
            ];
        }

        $permittedCamp->delete();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Camp permission removed successfully.',
            'data'    => [],
        ];
    }
}
