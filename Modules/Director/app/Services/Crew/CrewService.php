<?php

namespace Modules\Director\Services\Crew;

use App\Models\AssistantDirectorPermission;
use App\Models\CampRefereeJearsyNumber;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Director\Models\Camp;
use Modules\Director\Models\Crew;
use Modules\Director\Models\CrewMember;
use Modules\Director\Models\CampRefereeCheckin;
use Modules\Director\Models\GameSlot;
use Modules\Director\Models\RefereeAssignment;
use Modules\Director\Transformers\Referee\AvailableRefereeResource;
use Modules\Director\Transformers\Referee\CheckedInRefereeResource;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CrewService
{
    /**
     * Create a new crew (with optional members).
     *
     * @param  mixed   $user
     * @param  int     $campId
     * @param  Request $request
     * @return array
     */
    public function createCrew($user, int $campId, Request $request): array
    {
        $camp = Camp::forDirectorOrAssistant($user->id)->where('id', $campId)->first();

        if (!$camp) {
            return ['success' => false, 'code' => 404, 'message' => 'Camp not found.', 'data' => null];
        }

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return ['success' => false, 'code' => 403, 'message' => 'Unauthorized.', 'data' => null];
            }

            if (!$permission->manage_roster_crews) {
                return ['success' => false, 'code' => 403, 'message' => 'You do not have permission to manage crews for this camp.', 'data' => null];
            }
        }

        // Check for duplicate crew name in same camp
        $exists = Crew::where('camp_id', $campId)->where('name', $request->name)->exists();

        if ($exists) {
            return ['success' => false, 'code' => 400, 'message' => 'Crew with this name already exists in this camp.', 'data' => null];
        }

        DB::beginTransaction();
        try {
            $crew = Crew::create([
                'camp_id'     => $campId,
                'name'        => $request->name,
                'description' => $request->description,
                'status'      => 'active',
            ]);

            $added   = [];
            $skipped = [];

            $memberInputs = $this->extractMemberInputs($request);

            foreach ($memberInputs as $item) {
                $refereeId = $item['referee_id'];
                $position  = $item['position'] ?? null;

                if (!CampRefereeCheckin::where('camp_id', $campId)->where('referee_id', $refereeId)->exists()) {
                    $skipped[] = ['referee_id' => $refereeId, 'reason' => 'Not checked in to this camp'];
                    continue;
                }

                $alreadyInCrew = CrewMember::whereHas('crew', function ($q) use ($campId) {
                    $q->where('camp_id', $campId);
                })->where('referee_id', $refereeId)->exists();

                if ($alreadyInCrew) {
                    $skipped[] = ['referee_id' => $refereeId, 'reason' => 'Already assigned to another crew'];
                    continue;
                }

                CrewMember::create([
                    'crew_id'    => $crew->id,
                    'referee_id' => $refereeId,
                    'position'   => $position,
                    'joined_at'  => now(),
                ]);

                $added[] = ['referee_id' => $refereeId, 'position' => $position];
            }

            $crew->load('members');

            DB::commit();

            return [
                'success' => true,
                'code'    => 201,
                'message' => 'Crew created successfully.',
                'data'    => [
                    'crew' => [
                        'id'           => $crew->id,
                        'name'         => $crew->name,
                        'description'  => $crew->description,
                        'member_count' => $crew->members->count(),
                        'members'      => $crew->members->map(function ($member) {
                            return [
                                'id'        => $member->id,
                                'name'      => $member->first_name . ' ' . $member->last_name,
                                'email'     => $member->email,
                                'avatar'    => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                                'position'  => $member->pivot?->position ?? null,
                                'joined_at' => $member->pivot?->joined_at,
                            ];
                        }),
                    ],
                    'added_members'   => $added,
                    'skipped_members' => $skipped,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'code' => 500, 'message' => 'Failed to create crew: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Get all crews for a camp with member details and jersey numbers.
     *
     * @param  mixed $user
     * @param  int   $campId
     * @return array
     */
    public function getCrews($user, int $campId): array
    {
        $camp = Camp::forDirectorOrAssistant($user->id)->where('id', $campId)->first();

        if (!$camp) {
            return ['success' => false, 'code' => 404, 'message' => 'Camp not found.', 'data' => null];
        }

        $jerseyNumbers = CampRefereeJearsyNumber::where('camp_id', $campId)->pluck('jersey_number', 'referee_id');

        $crews = Crew::where('camp_id', $campId)
            ->withCount('members')
            ->with(['members' => function ($query) {
                $query->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.avatar', 'users.address', 'users.phone');
            }])
            ->get();

        $formatted = $crews->map(function ($crew) use ($jerseyNumbers) {
            return [
                'id'           => $crew->id,
                'name'         => $crew->name,
                'description'  => $crew->description,
                'status'       => $crew->status,
                'member_count' => $crew->members_count,
                'members'      => $crew->members->map(function ($member) use ($jerseyNumbers) {
                    return [
                        'id'             => $member->id,
                        'name'           => $member->first_name . ' ' . $member->last_name,
                        'email'          => $member->email,
                        'avatar'         => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                        'jersey_number'  => $jerseyNumbers[$member->id] ?? null,
                        'position'       => $member->pivot->position ?? null,
                        'joined_at'      => $member->pivot->joined_at,
                        'address'        => $member->address,
                        'phone'          => $member->phone,
                    ];
                }),
                'created_at' => $crew->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Crews fetched successfully.',
            'data'    => [
                'total_crews' => $formatted->count(),
                'crews'       => $formatted,
            ],
        ];
    }

    /**
     * Export camp crews as a streamed CSV or Excel download.
     * Returns the StreamedResponse directly (not a standard data array).
     *
     * @param  mixed   $user
     * @param  int     $campId
     * @param  string  $format
     * @return array|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportCampCrews($user, int $campId, string $format)
    {
        if (!$user || !$user->hasRole('director')) {
            return ['success' => false, 'code' => 403, 'message' => 'Unauthorized access. Only directors can export crews.', 'data' => null];
        }

        $camp = Camp::where('id', $campId)->where('director_id', $user->id)->first();

        if (!$camp) {
            return ['success' => false, 'code' => 404, 'message' => 'Camp not found.', 'data' => null];
        }

        $crews = Crew::where('camp_id', $campId)
            ->withCount('members')
            ->with(['members' => function ($query) {
                $query->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.phone');
            }])
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $campName    = $camp->camp_name ?? 'Camp';

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
            $membersList = $crew->members->map(function ($m) {
                $name    = trim(($m->first_name ?? '') . ' ' . ($m->last_name ?? ''));
                $pos     = $m->pivot->position ?? null;
                $details = array_filter([$pos ? "Position: {$pos}" : null, $m->email ?? '', $m->phone ?? '']);
                return !empty($details) ? $name . ' (' . implode(', ', $details) . ')' : $name;
            })->filter()->implode(' | ');

            $sheet->setCellValue('A' . $rowIndex, $crew->name ?: '-');
            $sheet->setCellValue('B' . $rowIndex, $crew->description ?: '-');
            $sheet->setCellValue('C' . $rowIndex, $crew->status ?: '-');
            $sheet->setCellValue('D' . $rowIndex, $crew->members_count ?? 0);
            $sheet->setCellValue('E' . $rowIndex, $membersList ?: '-');

            $rowIndex++;
        }

        $isExcel      = in_array($format, ['excel', 'xlsx']);
        $safeCampName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $campName);

        if ($isExcel) {
            $writer      = new Xlsx($spreadsheet);
            $fileName    = $safeCampName . '_crews_' . date('Y_m_d') . '.xlsx';
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            $writer = new Csv($spreadsheet);
            $writer->setUseBOM(true);
            $fileName    = $safeCampName . '_crews_' . date('Y_m_d') . '.csv';
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
     * Get crew details by ID.
     *
     * @param  int $crewId
     * @return array
     */
    public function getCrewDetails(int $crewId): array
    {
        $crew = Crew::with(['camp', 'members', 'gameSlots'])->withCount('members')->find($crewId);

        if (!$crew) {
            return ['success' => false, 'code' => 404, 'message' => 'Crew not found.', 'data' => null];
        }

        $jerseyNumbers = CampRefereeJearsyNumber::where('camp_id', $crew->camp->id)
            ->pluck('jersey_number', 'referee_id');

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Crew details fetched successfully.',
            'data'    => [
                'id'             => $crew->id,
                'name'           => $crew->name,
                'description'    => $crew->description,
                'status'         => $crew->status,
                'member_count'   => $crew->members_count,
                'members'        => $crew->members->map(function ($member) use ($jerseyNumbers) {
                    return [
                        'id'             => $member->id,
                        'name'           => $member->first_name . ' ' . $member->last_name ?? null,
                        'email'          => $member->email,
                        'avatar'         => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                        'position'       => $member->pivot?->position ?? null,
                        'joined_at'      => $member->pivot?->joined_at,
                        'jersey_number'  => $jerseyNumbers->get($member->id) ?? null,
                        'address'        => $member->address,
                        'phone'          => $member->phone,
                    ];
                }),
                'assigned_games' => $crew->gameSlots->count(),
                'camp'           => [
                    'id'   => $crew->camp->id,
                    'name' => $crew->camp->camp_name,
                ],
            ],
        ];
    }

    /**
     * Add referees to a crew.
     *
     * @param  mixed   $user
     * @param  int     $crewId
     * @param  Request $request
     * @return array
     */
    public function addMembers($user, int $crewId, Request $request): array
    {
        $crew = Crew::with('camp', 'members')->find($crewId);

        if (!$crew) {
            return ['success' => false, 'code' => 404, 'message' => 'Crew not found.', 'data' => null];
        }

        $camp = $crew->camp;

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return ['success' => false, 'code' => 403, 'message' => 'Unauthorized.', 'data' => null];
            }

            if (!$permission->manage_roster_crews) {
                return ['success' => false, 'code' => 403, 'message' => 'You do not have permission to manage crews for this camp.', 'data' => null];
            }
        }

        $memberInputs = $this->extractMemberInputs($request);

        if (empty($memberInputs)) {
            return ['success' => false, 'code' => 400, 'message' => 'No referees provided.', 'data' => null];
        }

        if ($crew->members->count() + count($memberInputs) > 8) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Crew can have max 8 members.',
                'data'    => [
                    'current_members' => $crew->members->count(),
                    'trying_to_add'   => count($memberInputs),
                ],
            ];
        }

        $added   = [];
        $skipped = [];

        foreach ($memberInputs as $item) {
            $refereeId = $item['referee_id'];
            $position  = $item['position'] ?? null;

            if (!CampRefereeCheckin::where('camp_id', $crew->camp_id)->where('referee_id', $refereeId)->exists()) {
                $skipped[] = ['referee_id' => $refereeId, 'reason' => 'Not checked in'];
                continue;
            }

            $existing = CrewMember::whereHas('crew', function ($q) use ($crew) {
                $q->where('camp_id', $crew->camp_id);
            })->where('referee_id', $refereeId)->first();

            if ($existing) {
                $skipped[] = ['referee_id' => $refereeId, 'reason' => 'Already in another crew'];
                continue;
            }

            CrewMember::create([
                'crew_id'    => $crewId,
                'referee_id' => $refereeId,
                'position'   => $position,
                'joined_at'  => now(),
            ]);

            $added[] = ['referee_id' => $refereeId, 'position' => $position];
        }

        $crew->load('members');

        return [
            'success' => true,
            'code'    => 201,
            'message' => 'Members processed.',
            'data'    => [
                'crew' => [
                    'id'           => $crew->id,
                    'name'         => $crew->name,
                    'member_count' => $crew->members->count(),
                    'members'      => $crew->members->map(function ($member) {
                        return [
                            'id'        => $member->id,
                            'name'      => $member->first_name . ' ' . $member->last_name,
                            'email'     => $member->email,
                            'position'  => $member->pivot->position ?? null,
                            'joined_at' => $member->pivot->joined_at,
                        ];
                    }),
                ],
                'added'   => $added,
                'skipped' => $skipped,
            ],
        ];
    }

    /**
     * Remove referees from a crew.
     *
     * @param  mixed $user
     * @param  int   $crewId
     * @param  array $refereeIds
     * @return array
     */
    public function removeMembers($user, int $crewId, array $refereeIds): array
    {
        $crew = Crew::with('camp')->find($crewId);

        if (!$crew) {
            return ['success' => false, 'code' => 404, 'message' => 'Crew not found.', 'data' => null];
        }

        $camp = $crew->camp;

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return ['success' => false, 'code' => 403, 'message' => 'Unauthorized.', 'data' => null];
            }

            if (!$permission->manage_roster_crews) {
                return ['success' => false, 'code' => 403, 'message' => 'You do not have permission to manage crews for this camp.', 'data' => null];
            }
        }

        $removed  = [];
        $notFound = [];

        foreach ($refereeIds as $refereeId) {
            $member = CrewMember::where('crew_id', $crewId)->where('referee_id', $refereeId)->first();

            if (!$member) {
                $notFound[] = $refereeId;
                continue;
            }

            $member->delete();
            $removed[] = $refereeId;
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Members removed successfully.',
            'data'    => [
                'crew_id'            => $crewId,
                'removed'            => $removed,
                'not_found_in_crew'  => $notFound,
            ],
        ];
    }

    /**
     * Update crew details and/or sync members.
     *
     * @param  mixed   $user
     * @param  int     $crewId
     * @param  Request $request
     * @return array
     */
    public function updateCrew($user, int $crewId, Request $request): array
    {
        $crew = Crew::with('camp')->find($crewId);

        if (!$crew) {
            return ['success' => false, 'code' => 404, 'message' => 'Crew not found.', 'data' => null];
        }

        if ($crew->camp->director_id !== $user->id) {
            return ['success' => false, 'code' => 403, 'message' => 'Unauthorized.', 'data' => null];
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
                    return ['success' => false, 'code' => 400, 'message' => 'Crew with this name already exists in this camp.', 'data' => null];
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

            if ($request->has('referee_ids') || $request->has('members')) {
                $memberInputs  = $this->extractMemberInputs($request);
                $newRefereeIds = array_column($memberInputs, 'referee_id');

                $currentMemberIds = CrewMember::where('crew_id', $crewId)->pluck('referee_id')->toArray();

                // Remove old members not in new list
                $toRemove = array_diff($currentMemberIds, $newRefereeIds);
                if (!empty($toRemove)) {
                    CrewMember::where('crew_id', $crewId)->whereIn('referee_id', $toRemove)->delete();
                    $removed = array_values($toRemove);
                }

                // Add or update members
                foreach ($memberInputs as $item) {
                    $refereeId = $item['referee_id'];
                    $position  = $item['position'] ?? null;

                    if (in_array($refereeId, $currentMemberIds)) {
                        if ($position !== null || $request->has('positions') || $request->has('members')) {
                            CrewMember::where('crew_id', $crewId)->where('referee_id', $refereeId)->update(['position' => $position]);
                            $updated[] = ['referee_id' => $refereeId, 'position' => $position];
                        }
                        continue;
                    }

                    if (!CampRefereeCheckin::where('camp_id', $crew->camp_id)->where('referee_id', $refereeId)->exists()) {
                        $skipped[] = ['referee_id' => $refereeId, 'reason' => 'Not checked in'];
                        continue;
                    }

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
                        'joined_at'  => now(),
                    ]);

                    $added[] = ['referee_id' => $refereeId, 'position' => $position];
                }
            }

            $crew->save();
            $crew->load('members');

            DB::commit();

            $membersData = $crew->members->map(function ($member) {
                return [
                    'id'        => $member->id,
                    'name'      => $member->first_name . ' ' . $member->last_name,
                    'email'     => $member->email,
                    'avatar'    => $member->avatar ? asset($member->avatar) : asset('default/profile.jpg'),
                    'position'  => $member->pivot?->position ?? null,
                    'joined_at' => $member->pivot?->joined_at ? Carbon::parse($member->pivot->joined_at)->format('Y-m-d H:i:s') : null,
                ];
            })->values();

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Crew updated successfully.',
                'data'    => [
                    'crew' => [
                        'id'           => $crew->id,
                        'name'         => $crew->name,
                        'description'  => $crew->description ?? null,
                        'status'       => $crew->status,
                        'member_count' => $membersData->count(),
                        'members'      => $membersData,
                    ],
                    'members_sync' => [
                        'added'   => $added,
                        'updated' => $updated,
                        'removed' => $removed,
                        'skipped' => $skipped,
                    ],
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Crew update failed: ' . $e->getMessage() . ' | Line: ' . $e->getLine());
            return ['success' => false, 'code' => 500, 'message' => 'Failed to update crew: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Delete a crew (only if not assigned to any game slots).
     *
     * @param  mixed $user
     * @param  int   $crewId
     * @return array
     */
    public function deleteCrew($user, int $crewId): array
    {
        $crew = Crew::with('camp')->find($crewId);

        if (!$crew) {
            return ['success' => false, 'code' => 404, 'message' => 'Crew not found.', 'data' => null];
        }

        $camp = $crew->camp;

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return ['success' => false, 'code' => 403, 'message' => 'Unauthorized.', 'data' => null];
            }

            if (!$permission->manage_roster_crews) {
                return ['success' => false, 'code' => 403, 'message' => 'You do not have permission to manage crews for this camp.', 'data' => null];
            }
        }

        $assignedGames = $crew->gameSlots()->count();

        if ($assignedGames > 0) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => "Cannot delete crew. It is assigned to {$assignedGames} game slot(s). Remove assignments first.",
                'data'    => ['assigned_games_count' => $assignedGames],
            ];
        }

        $crew->delete();

        return ['success' => true, 'code' => 200, 'message' => 'Crew deleted successfully.', 'data' => null];
    }

    /**
     * Assign crew to a game slot (legacy method using RefereeAssignment).
     *
     * @param  mixed   $user
     * @param  int     $gameSlotId
     * @param  int     $crewId
     * @return array
     */
    public function assignCrewToSlot($user, int $gameSlotId, int $crewId): array
    {
        $gameSlot = GameSlot::with('schedule.camp')->find($gameSlotId);

        if (!$gameSlot) {
            return ['success' => false, 'code' => 404, 'message' => 'Game slot not found.', 'data' => null];
        }

        if ($gameSlot->schedule->camp->director_id !== $user->id) {
            return ['success' => false, 'code' => 403, 'message' => 'Unauthorized.', 'data' => null];
        }

        $crew = Crew::with('members')->find($crewId);

        if ($crew->camp_id !== $gameSlot->schedule->camp_id) {
            return ['success' => false, 'code' => 400, 'message' => 'Crew does not belong to this camp.', 'data' => null];
        }

        if ($crew->members->isEmpty()) {
            return ['success' => false, 'code' => 400, 'message' => 'Crew has no members. Add members before assigning.', 'data' => null];
        }

        DB::beginTransaction();
        try {
            RefereeAssignment::where('game_slot_id', $gameSlotId)->delete();

            $gameSlot->update([
                'crew_id'         => $crew->id,
                'assignment_mode' => 'crew',
                'status'          => 'assigned',
            ]);

            foreach ($crew->members as $member) {
                RefereeAssignment::create([
                    'game_slot_id'    => $gameSlotId,
                    'referee_id'      => $member->id,
                    'crew_id'         => $crew->id,
                    'assignment_type' => 'manual',
                ]);
            }

            DB::commit();

            $gameSlot->load('crew', 'refereeAssignments.referee');

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Crew assigned to game slot successfully.',
                'data'    => [
                    'game_slot'               => $gameSlot,
                    'assigned_referees_count' => $crew->members->count(),
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'code' => 500, 'message' => 'Failed to assign crew: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Remove crew assignment from a game slot.
     *
     * @param  mixed $user
     * @param  int   $gameSlotId
     * @return array
     */
    public function removeCrewFromSlot($user, int $gameSlotId): array
    {
        $gameSlot = GameSlot::with('schedule.camp')->find($gameSlotId);

        if (!$gameSlot) {
            return ['success' => false, 'code' => 404, 'message' => 'Game slot not found.', 'data' => null];
        }

        if ($gameSlot->schedule->camp->director_id !== $user->id) {
            return ['success' => false, 'code' => 403, 'message' => 'Unauthorized.', 'data' => null];
        }

        if (!$gameSlot->isCrewAssignment()) {
            return ['success' => false, 'code' => 400, 'message' => 'This game slot is not assigned to a crew.', 'data' => null];
        }

        DB::beginTransaction();
        try {
            RefereeAssignment::where('game_slot_id', $gameSlotId)->delete();

            $gameSlot->update([
                'crew_id'         => null,
                'assignment_mode' => 'individual',
                'status'          => 'available',
            ]);

            DB::commit();

            return ['success' => true, 'code' => 200, 'message' => 'Crew removed from game slot successfully.', 'data' => null];
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'code' => 500, 'message' => 'Failed to remove crew: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * Get available referees for crew (checked-in but not assigned to any crew in this camp).
     *
     * @param  mixed   $user
     * @param  int     $campId
     * @param  Request $request
     * @return array
     */
    public function getAvailableReferees($user, int $campId, Request $request): array
    {
        $camp = Camp::forDirectorOrAssistant($user->id)->where('id', $campId)->first();

        if (!$camp) {
            return ['success' => false, 'code' => 404, 'message' => 'Camp not found.', 'data' => null];
        }

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return ['success' => false, 'code' => 403, 'message' => 'Unauthorized to approve this registration.', 'data' => []];
            }
        }

        $jerseyNumbers = CampRefereeJearsyNumber::where('camp_id', $camp->id)->pluck('jersey_number', 'referee_id');

        $checkedInRefereeIds = CampRefereeCheckin::where('camp_id', $campId)->pluck('referee_id');
        $assignedRefereeIds  = CrewMember::whereHas('crew', function ($q) use ($campId) {
            $q->where('camp_id', $campId);
        })->pluck('referee_id');

        $availableRefereeIds = $checkedInRefereeIds->diff($assignedRefereeIds);
        $perPage             = $request->get('per_page', 15);

        $availableReferees = User::whereIn('id', $availableRefereeIds)
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'avatar', 'address')
            ->orderBy('first_name')
            ->paginate($perPage);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Available referees fetched successfully.',
            'data'    => [
                'total_checked_in' => $checkedInRefereeIds->count(),
                'in_crews'         => $assignedRefereeIds->count(),
                'available'        => $availableReferees->total(),
                'referees'         => $availableReferees->getCollection()->map(function ($referee) use ($jerseyNumbers) {
                    return new AvailableRefereeResource($referee, $jerseyNumbers);
                }),
                'pagination' => [
                    'total'        => $availableReferees->total(),
                    'per_page'     => $availableReferees->perPage(),
                    'current_page' => $availableReferees->currentPage(),
                    'last_page'    => $availableReferees->lastPage(),
                ],
            ],
        ];
    }

    /**
     * Get all checked-in referees for a camp (director only).
     *
     * @param  mixed   $user
     * @param  int     $campId
     * @param  Request $request
     * @return array
     */
    public function getAllCheckedInReferees($user, int $campId, Request $request): array
    {
        $camp = Camp::where('id', $campId)->where('director_id', $user->id)->first();

        if (!$camp) {
            return ['success' => false, 'code' => 404, 'message' => 'Camp not found.', 'data' => null];
        }

        $jerseyNumbers = CampRefereeJearsyNumber::where('camp_id', $camp->id)->pluck('jersey_number', 'referee_id');
        $perPage       = $request->get('per_page', 15);

        $checkedInReferees = CampRefereeCheckin::where('camp_id', $campId)
            ->with('referee')
            ->paginate($perPage);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Checked-in referees fetched successfully.',
            'data'    => [
                'total'      => $checkedInReferees->total(),
                'referees'   => CheckedInRefereeResource::collectionWithJersey($checkedInReferees, $jerseyNumbers),
                'pagination' => [
                    'total'        => $checkedInReferees->total(),
                    'per_page'     => $checkedInReferees->perPage(),
                    'current_page' => $checkedInReferees->currentPage(),
                    'last_page'    => $checkedInReferees->lastPage(),
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Extract member inputs from either 'members' array or 'referee_ids' + 'positions'.
     *
     * @param  Request $request
     * @return array
     */
    private function extractMemberInputs(Request $request): array
    {
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
                $memberInputs[] = ['referee_id' => $refereeId, 'position' => $pos];
            }
        }

        return $memberInputs;
    }
}
