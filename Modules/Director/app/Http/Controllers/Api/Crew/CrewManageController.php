<?php

namespace Modules\Director\Http\Controllers\Api\Crew;

use App\Models\AssistantDirectorPermission;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\CampRefereeJearsyNumber;
use Modules\Director\Transformers\Referee\AvailableRefereeResource;
use Modules\Director\Transformers\Referee\CheckedInRefereeResource;
use Modules\Director\Models\{Camp, Crew, CrewMember, CampRefereeCheckin, GameSlot, RefereeAssignment};
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CrewManageController extends Controller
{
    use ApiResponse;


    /**
     * Create a new crew (with optional members)
     */
    public function createCrew(Request $request, $campId)
    {
        $user = auth('api')->user();

        // Validate
        $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string|max:500',
            'positions'     => 'nullable|array|max:8', // max 8 members
            'positions.*'   => 'nullable|string|max:255',
            'referee_ids'   => 'nullable|array|max:8', // max 8 members
            'referee_ids.*' => 'exists:users,id',
            'members'       => 'nullable|array|max:8',
            'members.*.referee_id' => 'required_with:members|exists:users,id',
            'members.*.position'   => 'nullable|string|max:255',
        ]);

        // Verify camp ownership or assistant director access
        $camp = Camp::forDirectorOrAssistant($user->id)
            ->where('id', $campId)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return $this->error('Unauthorized.', null, 403);
            }

            if (!$permission->manage_roster_crews) {
                return $this->error('You do not have permission to manage crews for this camp.', null, 403);
            }
        }

        // Check for duplicate crew name in same camp
        $exists = Crew::where('camp_id', $campId)
            ->where('name', $request->name)
            ->exists();

        if ($exists) {
            return $this->error('Crew with this name already exists in this camp.', null, 400);
        }

        DB::beginTransaction();
        try {
            // Create crew
            $crew = Crew::create([
                'camp_id'     => $campId,
                'name'        => $request->name,
                'description' => $request->description,
                'status'      => 'active'
            ]);

            $added = [];
            $skipped = [];

            // Extract member inputs from either members array or referee_ids + positions
            $memberInputs = [];
            if ($request->has('members') && is_array($request->members)) {
                foreach ($request->members as $item) {
                    if (isset($item['referee_id'])) {
                        $memberInputs[] = [
                            'referee_id' => $item['referee_id'],
                            'position'   => $item['position'] ?? $item['position_id'] ?? null,
                        ];
                    }
                }
            } elseif ($request->has('referee_ids') && is_array($request->referee_ids)) {
                $positions = $request->positions ?? [];
                foreach ($request->referee_ids as $index => $refereeId) {
                    $pos = null;
                    if (is_array($positions)) {
                        if (array_key_exists($refereeId, $positions)) {
                            $pos = $positions[$refereeId];
                        } elseif (array_key_exists($index, $positions)) {
                            $pos = $positions[$index];
                        }
                    }
                    $memberInputs[] = [
                        'referee_id' => $refereeId,
                        'position'   => $pos,
                    ];
                }
            }

            // If members provided, add them
            if (!empty($memberInputs)) {
                foreach ($memberInputs as $item) {
                    $refereeId = $item['referee_id'];
                    $position  = $item['position'] ?? null;

                    // 1. Must be checked in
                    $isCheckedIn = CampRefereeCheckin::where('camp_id', $campId)
                        ->where('referee_id', $refereeId)
                        ->exists();

                    if (!$isCheckedIn) {
                        $skipped[] = [
                            'referee_id' => $refereeId,
                            'reason'     => 'Not checked in to this camp'
                        ];
                        continue;
                    }

                    // 2. Not already in any crew in this camp
                    $alreadyInCrew = CrewMember::whereHas('crew', function ($q) use ($campId) {
                        $q->where('camp_id', $campId);
                    })->where('referee_id', $refereeId)->exists();

                    if ($alreadyInCrew) {
                        $skipped[] = [
                            'referee_id' => $refereeId,
                            'reason'     => 'Already assigned to another crew'
                        ];
                        continue;
                    }

                    // Add to crew
                    CrewMember::create([
                        'crew_id'    => $crew->id,
                        'referee_id' => $refereeId,
                        'position'   => $position,
                        'joined_at'  => now()
                    ]);

                    $added[] = [
                        'referee_id' => $refereeId,
                        'position'   => $position,
                    ];
                }
            }

            // Reload crew with members
            $crew->load('members');

            DB::commit();

            return $this->success(
                'Crew created successfully.',
                [
                    'crew' => [
                        'id'            => $crew->id,
                        'name'          => $crew->name,
                        'description'   => $crew->description,
                        'member_count'  => $crew->members->count(),
                        'members' => $crew->members->map(function ($member) {
                            return [
                                'id'        => $member->id,
                                'name'      => $member->first_name . ' ' . $member->last_name,
                                'email'     => $member->email,
                                'avatar'    => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                                'position'  => $member->pivot?->position ?? null,
                                'joined_at' => $member->pivot?->joined_at
                            ];
                        })
                    ],
                    'added_members'   => $added,
                    'skipped_members' => $skipped
                ],
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create crew: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get all crews for a camp
     */
    public function getCrews($campId)
    {
        $user = auth('api')->user();

        // Verify camp ownership
        $camp = Camp::forDirectorOrAssistant($user->id)
            ->where('id', $campId)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $jerseyNumbers = CampRefereeJearsyNumber::where('camp_id', $campId)
            ->pluck('jersey_number', 'referee_id');

        $crews = Crew::where('camp_id', $campId)
            ->withCount('members')
            ->with(['members' => function ($query) {
                $query->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.avatar', 'users.address', 'users.phone');
            }])
            ->get();

        $formatted = $crews->map(function ($crew) use ($jerseyNumbers) {
            return [
                'id' => $crew->id,
                'name' => $crew->name,
                'description' => $crew->description,
                'status' => $crew->status,
                'member_count' => $crew->members_count,
                'members' => $crew->members->map(function ($member) use ($jerseyNumbers) {
                    return [
                        'id' => $member->id,
                        'name' => $member->first_name . ' ' . $member->last_name,
                        'email' => $member->email,
                        'avatar' => $member->avatar
                            ? asset($member->avatar)
                            : asset('default/profile.jpg'),

                        // jersey number here
                        'jersey_number' => $jerseyNumbers[$member->id] ?? null,
                        'position' => $member->pivot->position ?? null,
                        'joined_at' => $member->pivot->joined_at,
                        'address' => $member->address,
                        'phone' => $member->phone,
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
     * Export camp crews data as CSV or Excel (Director only)
     */
    public function exportCampCrews(Request $request, $campId)
    {
        $user = auth('api')->user();

        if (!$user || !$user->hasRole('director')) {
            return $this->error('Unauthorized access. Only directors can export crews.', null, 403);
        }

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
                $query->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.phone');
            }])
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $campName = $camp->camp_name ?? 'Camp';

        // Title Row
        $sheet->setCellValue('A1', 'Camp Name:');
        $sheet->setCellValue('B1', $campName . ' - Crews Export');

        // Header Row
        $headers = [
            'A3' => 'Crew Name',
            'B3' => 'Description',
            'C3' => 'Status',
            'D3' => 'Member Count',
            'E3' => 'Members',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $rowIndex = 4;
        foreach ($crews as $crew) {
            $crewName = $crew->name ?? '-';
            $description = $crew->description ?? '-';
            $status = $crew->status ?? '-';
            $memberCount = $crew->members_count ?? 0;

            $membersList = $crew->members->map(function ($m) {
                $name = trim(($m->first_name ?? '') . ' ' . ($m->last_name ?? ''));
                $pos = $m->pivot->position ?? null;
                $details = array_filter([$pos ? "Position: {$pos}" : null, $m->email ?? '', $m->phone ?? '']);
                if (!empty($details)) {
                    return $name . ' (' . implode(', ', $details) . ')';
                }
                return $name;
            })->filter()->implode(' | ');

            $sheet->setCellValue('A' . $rowIndex, !empty($crewName) ? $crewName : '-');
            $sheet->setCellValue('B' . $rowIndex, !empty($description) ? $description : '-');
            $sheet->setCellValue('C' . $rowIndex, !empty($status) ? $status : '-');
            $sheet->setCellValue('D' . $rowIndex, $memberCount);
            $sheet->setCellValue('E' . $rowIndex, !empty($membersList) ? $membersList : '-');

            $rowIndex++;
        }

        $format = strtolower($request->query('format', $request->query('type', 'csv')));
        $isExcel = in_array($format, ['excel', 'xlsx']);

        $safeCampName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $campName);

        if ($isExcel) {
            $writer = new Xlsx($spreadsheet);
            $fileName = $safeCampName . '_crews_' . date('Y_m_d') . '.xlsx';
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            $writer = new Csv($spreadsheet);
            $writer->setUseBOM(true);
            $fileName = $safeCampName . '_crews_' . date('Y_m_d') . '.csv';
            $contentType = 'text/csv; charset=UTF-8';
        }

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'  => $contentType,
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Pragma'        => 'public',
        ]);
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
        // if ($crew->camp->director_id !== $user->id) {
        //     return $this->error('Unauthorized.', null, 403);
        // }

        // Get camp-specific jersey number
        $jerseyNumbers = CampRefereeJearsyNumber::where('camp_id', $crew->camp->id)
            ->pluck('jersey_number', 'referee_id');

        return $this->success(
            'Crew details fetched successfully.',
            [
                'id' => $crew->id,
                'name' => $crew->name,
                'description' => $crew->description,
                'status' => $crew->status,
                'member_count' => $crew->members_count,
                'members' => $crew->members->map(function ($member) use ($jerseyNumbers) {
                    return [
                        'id' => $member->id,
                        'name' => $member->first_name . ' ' . $member->last_name ?? null,
                        'email' => $member->email,
                        'avatar' => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                        'position' => $member->pivot?->position ?? null,
                        'joined_at' => $member->pivot?->joined_at,
                        'jersey_number' => $jerseyNumbers->get($member->id) ?? null,
                        'address' => $member->address,
                        'phone' => $member->phone
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
            'referee_ids'   => 'nullable|array|min:1|max:8',
            'referee_ids.*' => 'exists:users,id',
            'positions'     => 'nullable|array|max:8',
            'positions.*'   => 'nullable|string|max:255',
            'members'       => 'nullable|array|min:1|max:8',
            'members.*.referee_id' => 'required_with:members|exists:users,id',
            'members.*.position'   => 'nullable|string|max:255',
        ]);

        $crew = Crew::with('camp', 'members')->find($crewId);

        if (!$crew) {
            return $this->error('Crew not found.', null, 404);
        }

        // Verify ownership or assistant director access
        $camp = $crew->camp;

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return $this->error('Unauthorized.', null, 403);
            }

            if (!$permission->manage_roster_crews) {
                return $this->error('You do not have permission to manage crews for this camp.', null, 403);
            }
        }

        $memberInputs = [];
        if ($request->has('members') && is_array($request->members)) {
            foreach ($request->members as $item) {
                if (isset($item['referee_id'])) {
                    $memberInputs[] = [
                        'referee_id' => $item['referee_id'],
                        'position'   => $item['position'] ?? $item['position_id'] ?? null,
                    ];
                }
            }
        } elseif ($request->has('referee_ids') && is_array($request->referee_ids)) {
            $positions = $request->positions ?? [];
            foreach ($request->referee_ids as $index => $refereeId) {
                $pos = null;
                if (is_array($positions)) {
                    if (array_key_exists($refereeId, $positions)) {
                        $pos = $positions[$refereeId];
                    } elseif (array_key_exists($index, $positions)) {
                        $pos = $positions[$index];
                    }
                }
                $memberInputs[] = [
                    'referee_id' => $refereeId,
                    'position'   => $pos,
                ];
            }
        }

        if (empty($memberInputs)) {
            return $this->error('No referees provided.', null, 400);
        }

        // --- Capacity Check ---
        if ($crew->members->count() + count($memberInputs) > 8) {
            return $this->error(
                'Crew can have max 8 members.',
                [
                    'current_members' => $crew->members->count(),
                    'trying_to_add' => count($memberInputs)
                ],
                400
            );
        }

        $added = [];
        $skipped = [];

        foreach ($memberInputs as $item) {
            $refereeId = $item['referee_id'];
            $position  = $item['position'] ?? null;

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
            CrewMember::create([
                'crew_id'    => $crewId,
                'referee_id' => $refereeId,
                'position'   => $position,
                'joined_at'  => now()
            ]);

            $added[] = [
                'referee_id' => $refereeId,
                'position'   => $position,
            ];
        }

        $crew->load('members');

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
                            'position' => $member->pivot->position ?? null,
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

        // Verify ownership or assistant director access
        $camp = $crew->camp;

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return $this->error('Unauthorized.', null, 403);
            }

            if (!$permission->manage_roster_crews) {
                return $this->error('You do not have permission to manage crews for this camp.', null, 403);
            }
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
     * Update crew details and/or sync members
     */
    public function updateCrew(Request $request, $crewId)
    {
        $user = auth('api')->user();

        $request->validate([
            'name'        => 'string|max:255',
            'description' => 'nullable|string|max:500',
            'status'      => 'sometimes|in:active,inactive',
            'referee_ids' => 'nullable|array|max:8',
            'referee_ids.*' => 'exists:users,id',
            'positions'   => 'nullable|array|max:8',
            'positions.*' => 'nullable|string|max:255',
            'members'     => 'nullable|array|max:8',
            'members.*.referee_id' => 'required_with:members|exists:users,id',
            'members.*.position'   => 'nullable|string|max:255',
        ]);

        $crew = Crew::with('camp')->find($crewId);

        if (!$crew) {
            return $this->error('Crew not found.', null, 404);
        }

        // Verify ownership
        if ($crew->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        DB::beginTransaction();
        try {
            // Update basic fields
            if ($request->filled('name') && $request->name !== $crew->name) {
                $exists = Crew::where('camp_id', $crew->camp_id)
                    ->where('name', $request->name)
                    ->where('id', '!=', $crewId)
                    ->exists();

                if ($exists) {
                    return $this->error('Crew with this name already exists in this camp.', null, 400);
                }
                $crew->name = $request->name;
            }

            if ($request->has('description')) {
                $crew->description = $request->description;
            }

            if ($request->filled('status')) {
                $crew->status = $request->status;
            }

            $added   = [];
            $skipped = [];
            $removed = [];
            $updated = [];

            // Member sync if referee_ids or members provided
            if ($request->has('referee_ids') || $request->has('members')) {
                $memberInputs = [];
                if ($request->has('members') && is_array($request->members)) {
                    foreach ($request->members as $item) {
                        if (isset($item['referee_id'])) {
                            $memberInputs[] = [
                                'referee_id' => $item['referee_id'],
                                'position'   => $item['position'] ?? $item['position_id'] ?? null,
                            ];
                        }
                    }
                } elseif ($request->has('referee_ids') && is_array($request->referee_ids)) {
                    $positions = $request->positions ?? [];
                    foreach ($request->referee_ids as $index => $refereeId) {
                        $pos = null;
                        if (is_array($positions)) {
                            if (array_key_exists($refereeId, $positions)) {
                                $pos = $positions[$refereeId];
                            } elseif (array_key_exists($index, $positions)) {
                                $pos = $positions[$index];
                            }
                        }
                        $memberInputs[] = [
                            'referee_id' => $refereeId,
                            'position'   => $pos,
                        ];
                    }
                }

                $newRefereeIds = array_column($memberInputs, 'referee_id');

                // Current members
                $currentMemberIds = CrewMember::where('crew_id', $crewId)
                    ->pluck('referee_id')
                    ->toArray();

                // Remove old members not in new list
                $toRemove = array_diff($currentMemberIds, $newRefereeIds);
                if (!empty($toRemove)) {
                    CrewMember::where('crew_id', $crewId)
                        ->whereIn('referee_id', $toRemove)
                        ->delete();
                    $removed = array_values($toRemove);
                }

                // Add or update members
                foreach ($memberInputs as $item) {
                    $refereeId = $item['referee_id'];
                    $position  = $item['position'] ?? null;

                    if (in_array($refereeId, $currentMemberIds)) {
                        // Already in crew, update position if provided
                        if ($position !== null || $request->has('positions') || $request->has('members')) {
                            CrewMember::where('crew_id', $crewId)
                                ->where('referee_id', $refereeId)
                                ->update(['position' => $position]);
                            $updated[] = [
                                'referee_id' => $refereeId,
                                'position'   => $position
                            ];
                        }
                        continue;
                    }

                    // Check-in validation
                    $checkedIn = CampRefereeCheckin::where('camp_id', $crew->camp_id)
                        ->where('referee_id', $refereeId)
                        ->exists();

                    if (!$checkedIn) {
                        $skipped[] = ['referee_id' => $refereeId, 'reason' => 'Not checked in'];
                        continue;
                    }

                    // Not in other crew
                    $inOtherCrew = CrewMember::whereHas('crew', function ($q) use ($crew) {
                        $q->where('camp_id', $crew->camp_id)->where('id', '!=', $crew->id);
                    })->where('referee_id', $refereeId)->exists();

                    if ($inOtherCrew) {
                        $skipped[] = ['referee_id' => $refereeId, 'reason' => 'Already in another crew'];
                        continue;
                    }

                    CrewMember::create([
                        'crew_id'    => $crew->id,
                        'referee_id' => $refereeId,
                        'position'   => $position,
                        'joined_at'  => now()
                    ]);

                    $added[] = [
                        'referee_id' => $refereeId,
                        'position'   => $position
                    ];
                }
            }

            $crew->save();

            // Reload crew with members
            $crew->load('members');

            DB::commit();

            $membersData = $crew->members->map(function ($member) {
                return [
                    'id'        => $member->id,
                    'name'      => $member->first_name . ' ' . $member->last_name,
                    'email'     => $member->email,
                    'avatar'    => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                    'position'  => $member->pivot?->position ?? null,
                    'joined_at' => $member->pivot?->joined_at ? Carbon::parse($member->pivot->joined_at)->format('Y-m-d H:i:s') : null
                ];
            })->values();

            return $this->success(
                'Crew updated successfully.',
                [
                    'crew' => [
                        'id'           => $crew->id,
                        'name'         => $crew->name,
                        'description'  => $crew->description ?? null,
                        'status'       => $crew->status,
                        'member_count' => $membersData->count(),
                        'members'      => $membersData
                    ],
                    'members_sync' => [
                        'added'   => $added,
                        'updated' => $updated,
                        'removed' => $removed,
                        'skipped' => $skipped
                    ]
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Crew update failed: ' . $e->getMessage() . ' | Line: ' . $e->getLine());
            return $this->error('Failed to update crew: ' . $e->getMessage(), null, 500);
        }
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

        // Verify ownership or assistant director access
        $camp = $crew->camp;

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return $this->error('Unauthorized.', null, 403);
            }

            if (!$permission->manage_roster_crews) {
                return $this->error('You do not have permission to manage crews for this camp.', null, 403);
            }
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
        $camp = Camp::forDirectorOrAssistant($user->id)
            ->where('id', $campId)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return $this->error([], 'Unauthorized to approve this registration.', 403);
            }
        }

        $jerseyNumbers = CampRefereeJearsyNumber::where('camp_id', $camp->id)
            ->pluck('jersey_number', 'referee_id');

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
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'avatar', 'phone', 'address')
            ->orderBy('first_name')
            ->paginate($perPage);

        return $this->success('Available referees fetched successfully.', [
            'total_checked_in' => $checkedInRefereeIds->count(),
            'in_crews'         => $assignedRefereeIds->count(),
            'available'        => $availableReferees->total(),
            // 'referees' => AvailableRefereeResource::collection($availableReferees, $jerseyNumbers),
            'referees' => $availableReferees->getCollection()->map(function ($referee) use ($jerseyNumbers) {
                return new AvailableRefereeResource($referee, $jerseyNumbers);
            }),

            'pagination'       => [
                'total'         => $availableReferees->total(),
                'per_page'      => $availableReferees->perPage(),
                'current_page'  => $availableReferees->currentPage(),
                'last_page'     => $availableReferees->lastPage(),
            ],
        ], 200);
    }

    /**
     * Get all checked-in referees for a camp
     */
    public function getAllCheckedInReferees($campId)
    {
        $user = auth('api')->user();

        // Verify camp ownership
        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $jerseyNumbers = CampRefereeJearsyNumber::where('camp_id', $camp->id)
            ->pluck('jersey_number', 'referee_id');

        $perPage = request()->get('per_page', 15); // default 15

        $checkedInReferees = CampRefereeCheckin::where('camp_id', $campId)
            ->with('referee')
            ->paginate($perPage);

        return $this->success('Checked-in referees fetched successfully.', [
            'total' => $checkedInReferees->total(),
            'referees' => CheckedInRefereeResource::collectionWithJersey(
                $checkedInReferees,
                $jerseyNumbers
            ),
            'pagination' => [
                'total'         => $checkedInReferees->total(),
                'per_page'      => $checkedInReferees->perPage(),
                'current_page'  => $checkedInReferees->currentPage(),
                'last_page'     => $checkedInReferees->lastPage(),
            ],
        ], 200);
    }
}
