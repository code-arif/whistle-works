<?php

namespace App\Http\Controllers\Api\Frontend\DirectorCampManage;

use App\Models\AssistantDirectorPermission;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Director\Models\Camp;
use App\Notifications\AssistantDirectorAssignedNotification;
use App\Mail\AssistantDirectorAssignedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AssistantDirectorPermissionController extends Controller
{
    use ApiResponse;

    public function assistantDirectorList(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'camp_id' => [
                'nullable',
                'integer',
                'exists:camps,id',
            ],
            'search' => [
                'nullable',
                'string',
            ],
        ]);

        $search = $validated['search'] ?? null;
        $campId = $validated['camp_id'] ?? null;

        $directors = User::role('director')
            ->where('id', '!=', Auth::id())
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'avatar')
            ->get();

        $permissions = $campId
            ? AssistantDirectorPermission::where('camp_id', $campId)->get()->keyBy('assistant_director_id')
            : collect();

        $data = $directors->map(function ($director) use ($permissions, $campId) {
            /** @var AssistantDirectorPermission|null $permission */
            $permission = $campId ? $permissions->get($director->id) : null;
            $isAssigned = $permission !== null;

            return [
                'id'                     => $director->id,
                'first_name'             => $director->first_name,
                'last_name'              => $director->last_name,
                'email'                  => $director->email,
                'phone'                  => $director->phone,
                'avatar'                 => $director->avatar,
                'is_assigned'            => $isAssigned,
                'permissions'            => $permission ? [
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

        return $this->success('Director list retrieved successfully.', $data, 200);

    }

    public function storeOrUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assistant_director_id' => [
                'required',
                'integer',
                'exists:users,id',
                'not_in:' . Auth::id(),
            ],

            'camp_id' => [
                'required',
                'integer',
                'exists:camps,id',
            ],

            'permissions' => [
                'sometimes',
                'array',
            ],

            'build_schedule' => [
                'sometimes',
                'boolean',
            ],

            'assign_referees' => [
                'sometimes',
                'boolean',
            ],

            'publish_camp' => [
                'sometimes',
                'boolean',
            ],

            'manage_ranking_reports' => [
                'sometimes',
                'boolean',
            ],

            'manage_roster_referees' => [
                'sometimes',
                'boolean',
            ],

            'manage_roster_evaluators' => [
                'sometimes',
                'boolean',
            ],

            'manage_roster_crews' => [
                'sometimes',
                'boolean',
            ],

            'manage_announcements' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $user = auth('api')->user();
        $assistantDirector = User::role('director')->where('id', $validated['assistant_director_id'])->first();

        if (!$assistantDirector) {
            return $this->error([], 'Assistant Director not found.', 404);
        }

        $camp = Camp::find($validated['camp_id']);
        if (!$camp) {
            return $this->error([], 'Camp not found.', 404);
        }

        $permissionKeys = [
            'build_schedule',
            'assign_referees',
            'publish_camp',
            'manage_ranking_reports',
            'manage_roster_referees',
            'manage_roster_evaluators',
            'manage_roster_crews',
            'manage_announcements',
        ];

        // Handle permissions payload (either nested under 'permissions' or top-level)
        $nestedPermissions = $validated['permissions'] ?? [];
        $permissionData = [];

        foreach ($permissionKeys as $key) {
            if (array_key_exists($key, $nestedPermissions)) {
                $permissionData[$key] = (bool) $nestedPermissions[$key];
            } elseif (array_key_exists($key, $validated)) {
                $permissionData[$key] = (bool) $validated[$key];
            }
        }

        // Check BEFORE creating if this director was ALREADY assigned as an assistant director before (for any camp)
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
                'director_id'           => $user->id,
                'assistant_director_id' => $assistantDirector->id,
                'camp_id'               => $validated['camp_id'],
            ];

            foreach ($permissionKeys as $key) {
                $insertData[$key] = $permissionData[$key] ?? false;
            }

            $permission = AssistantDirectorPermission::create($insertData);
            $wasCreated = true;
        }

        // 1. Send System Notification (in-app database notification) to Assistant Director ($assistantDirector)
        try {
            $assistantDirector->notify(new AssistantDirectorAssignedNotification($camp, $user, $assistantDirector));
        } catch (\Exception $e) {
            Log::warning('Failed to send system notification: ' . $e->getMessage());
        }

        // 2. Send Mail ONLY IF this director is being assigned as an Assistant Director for the FIRST TIME
        if (!$isAlreadyAssistantDirector) {
            try {
                Mail::to($assistantDirector->email)->send(new AssistantDirectorAssignedMail($assistantDirector, $user, $camp));
            } catch (\Exception $e) {
                Log::warning('Failed to send assignment email to assistant director: ' . $e->getMessage());
            }
        }

        return $this->success(
            $wasCreated
                ? 'Assistant director permissions created successfully.'
                : 'Assistant director permissions updated successfully.',
            $permission,
            $wasCreated ? 201 : 200
        );
    }

    public function assistantDirectorCampList(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $today = now()->toDateString();

        // Get camp IDs where the logged-in user has assistant director access.
        $permittedCampIds = AssistantDirectorPermission::query()
            ->where('assistant_director_id', $user->id)
            ->pluck('camp_id');

        // Fetch camps created by the logged-in director
        $camps = Camp::query()
            ->whereIn('id', $permittedCampIds)
            ->with(['sportsType', 'checkedInReferees', 'schedule'])
            ->orderBy('created_at', 'desc')
            ->paginate(8);

        $response = [
            'camp_list' => $camps->map(function ($camp) {
                $assistantDirector = $camp->assistantDirectorPermissions->where('assistant_director_id', auth('api')->user()->id);

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
                    'total_courts'    => $camp->schedule ? $camp->schedule->gameSlots()->count() : 0,

                    // metadata
                    'director_id'    => $camp->director_id,
                    'created_at'     => $camp->created_at->format('Y-m-d H:i:s'),
                    'updated_at'     => $camp->updated_at->format('Y-m-d H:i:s'),

                    // Permission
                    'permissions'    => $assistantDirector,
                ];
            }),

            'pagination' => [
                'total'         => $camps->total(),
                'per_page'      => $camps->perPage(),
                'current_page'  => $camps->currentPage(),
                'last_page'     => $camps->lastPage(),
            ],
        ];

        return $this->success(
            'Camps fetched successfully.',
            $response,
            200
        );
    }

    public function assistantDirectorCampPermissionRemove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assistant_director_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'camp_id' => [
                'required',
                'integer',
                'exists:camps,id',
            ],
        ]);

        $user = auth('api')->user();

        $permittedCamp = AssistantDirectorPermission::where('director_id', $user->id)
            ->where('assistant_director_id', $validated['assistant_director_id'])
            ->where('camp_id', $validated['camp_id'])
            ->first();

        if (!$permittedCamp) {
            return $this->error([], 'Permission not found.', 404);
        }

        $permittedCamp->delete();

        return $this->success(
            'Camp permission removed successfully.',
            [],
            200
        );
    }

}
