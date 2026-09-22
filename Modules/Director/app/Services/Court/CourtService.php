<?php

namespace Modules\Director\Services\Court;

use App\Models\AssistantDirectorPermission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Director\Models\GameSlot;
use Modules\Director\Models\Schedule;
use Modules\Director\Models\ScheduleLocation;
use Modules\Director\Models\GameSlotAssignment;

class CourtService
{
    /**
     * Toggle block/unblock a single court slot.
     * When blocking: removes all assignments (crew + individual).
     *
     * @param  mixed $user
     * @param  int   $slotId
     * @return array
     */
    public function toggleBlockSlot($user, int $slotId): array
    {
        $slot = GameSlot::with('schedule.camp')->find($slotId);

        if (!$slot) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Game slot not found!',
                'data'    => null,
            ];
        }

        // Verify ownership or assistant director access
        $camp = $slot->schedule?->camp;

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'Unauthorized.',
                    'data'    => null,
                ];
            }

            if (!$permission->assign_referees) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to block the court.',
                    'data'    => null,
                ];
            }
        }

        DB::beginTransaction();
        try {
            $newBlockStatus = !$slot->is_block;

            if ($newBlockStatus) {
                // Blocking: remove all assignments and reset status to available
                $deletedCount = GameSlotAssignment::where('game_slot_id', $slotId)->count();
                GameSlotAssignment::where('game_slot_id', $slotId)->delete();

                $slot->update([
                    'is_block' => $newBlockStatus,
                    'status'   => 'available',
                ]);

                DB::commit();

                return [
                    'success' => true,
                    'code'    => 200,
                    'message' => 'Slot blocked successfully. All assignments removed.',
                    'data'    => [
                        'slot_id'             => $slotId,
                        'is_blocked'          => $newBlockStatus,
                        'assignments_removed' => $deletedCount,
                    ],
                ];
            } else {
                // Unblocking: no assignment changes
                $slot->update(['is_block' => $newBlockStatus]);

                DB::commit();

                return [
                    'success' => true,
                    'code'    => 200,
                    'message' => 'Slot unblocked successfully.',
                    'data'    => [
                        'slot_id'    => $slotId,
                        'is_blocked' => $newBlockStatus,
                    ],
                ];
            }
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to toggle block status: ' . $e->getMessage(),
                'data'    => null,
            ];
        }
    }

    /**
     * Update court name for a location.
     * Updates all game slots with the matching court number to the new name.
     *
     * @param  mixed  $user
     * @param  int    $locationId
     * @param  int    $courtNumber
     * @param  string $newCourtName
     * @return array
     */
    public function updateCourtName($user, int $locationId, int $courtNumber, string $newCourtName): array
    {
        $location = ScheduleLocation::with('schedule.camp')->find($locationId);

        if (!$location) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Location not found.',
                'data'    => null,
            ];
        }

        // Authorization check
        if ($location->schedule->camp->director_id !== $user->id) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Unauthorized.',
                'data'    => null,
            ];
        }

        // Validate court number
        if ($courtNumber < 1 || $courtNumber > $location->court_count) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => "Invalid court number. Location has {$location->court_count} courts.",
                'data'    => null,
            ];
        }

        $oldCourtName = "Court {$courtNumber}";

        DB::beginTransaction();
        try {
            $updated = GameSlot::where('schedule_location_id', $locationId)
                ->where('court_number', $courtNumber)
                ->update(['court_name' => $newCourtName]);

            DB::commit();

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Court name updated successfully.',
                'data'    => [
                    'location_id'    => $locationId,
                    'court_number'   => $courtNumber,
                    'old_court_name' => $oldCourtName,
                    'new_court_name' => $newCourtName,
                    'slots_updated'  => $updated,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to update court name: ' . $e->getMessage(),
                'data'    => null,
            ];
        }
    }

    /**
     * Bulk update court name for a specific court number within a location.
     * Updates ALL time slots for that court across all dates.
     * Supports assistant director access.
     *
     * @param  mixed  $user
     * @param  int    $locationId
     * @param  int    $courtNumber
     * @param  string $newCourtName
     * @return array
     */
    public function bulkUpdateCourtNames($user, int $locationId, int $courtNumber, string $newCourtName): array
    {
        $location = ScheduleLocation::with('schedule.camp')->find($locationId);

        if (!$location) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Location not found.',
                'data'    => null,
            ];
        }

        // Verify ownership or assistant director access
        $camp = $location->schedule?->camp;

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'Unauthorized.',
                    'data'    => null,
                ];
            }

            if (!$permission->assign_referees) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to change this court name.',
                    'data'    => null,
                ];
            }
        }

        // Validate court number
        if ($courtNumber > $location->court_count) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => "Invalid court number. Location has {$location->court_count} courts.",
                'data'    => null,
            ];
        }

        // Get old court name (from first slot)
        $oldCourtName = GameSlot::where('schedule_location_id', $locationId)
            ->where('court_number', $courtNumber)
            ->value('court_name') ?? "Court {$courtNumber}";

        DB::beginTransaction();
        try {
            $updated = GameSlot::where('schedule_location_id', $locationId)
                ->where('court_number', $courtNumber)
                ->update(['court_name' => $newCourtName]);

            DB::commit();

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Court name updated successfully for all time slots.',
                'data'    => [
                    'location_id'         => $locationId,
                    'location_name'       => $location->location_name,
                    'court_number'        => $courtNumber,
                    'old_court_name'      => $oldCourtName,
                    'new_court_name'      => $newCourtName,
                    'total_slots_updated' => $updated,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to update court name: ' . $e->getMessage(),
                'data'    => null,
            ];
        }
    }

    /**
     * Get all court names for a location, grouped by court number.
     *
     * @param  mixed $user
     * @param  int   $locationId
     * @return array
     */
    public function getCourtNames($user, int $locationId): array
    {
        $location = ScheduleLocation::with('schedule.camp')->find($locationId);

        if (!$location) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Location not found.',
                'data'    => null,
            ];
        }

        // Authorization check
        if ($location->schedule->camp->director_id !== $user->id) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Unauthorized.',
                'data'    => null,
            ];
        }

        $courtNames = GameSlot::where('schedule_location_id', $locationId)
            ->select('court_number', 'court_name')
            ->groupBy('court_number', 'court_name')
            ->orderBy('court_number')
            ->get()
            ->map(function ($slot) {
                return [
                    'court_number' => $slot->court_number,
                    'court_name'   => $slot->court_name,
                ];
            });

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Court names fetched successfully.',
            'data'    => [
                'location_id'   => $locationId,
                'location_name' => $location->location_name,
                'court_count'   => $location->court_count,
                'court_names'   => $courtNames,
            ],
        ];
    }

    /**
     * Block or unblock all courts at a specific time on a specific date.
     * When blocking: removes all assignments from affected slots.
     *
     * @param  mixed   $user
     * @param  int     $scheduleId
     * @param  Request $request
     * @return array
     */
    public function bulkToggleBlockByTime($user, int $scheduleId, Request $request): array
    {
        $schedule = Schedule::with('camp')->findOrFail($scheduleId);

        // Verify ownership or assistant director access
        $camp = $schedule->camp;

        if ($camp->director_id !== $user->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $user->id)
                ->first();

            if (!$permission) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'Unauthorized.',
                    'data'    => null,
                ];
            }

            if (!$permission->assign_referees) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to block this court row.',
                    'data'    => null,
                ];
            }
        }

        // Get all slots matching date and time
        $slots = GameSlot::where('schedule_id', $scheduleId)
            ->where('game_date', $request->date)
            ->where('start_time', $request->start_time)
            ->get();

        if ($slots->isEmpty()) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'No slots found for the given date and time.',
                'data'    => null,
            ];
        }

        DB::beginTransaction();
        try {
            $newBlockStatus          = ($request->action === 'block');
            $slotIds                 = $slots->pluck('id');
            $totalAssignmentsRemoved = 0;

            if ($newBlockStatus) {
                // Blocking: remove all assignments and reset to available
                $totalAssignmentsRemoved = GameSlotAssignment::whereIn('game_slot_id', $slotIds)->count();
                GameSlotAssignment::whereIn('game_slot_id', $slotIds)->delete();

                $affectedCount = GameSlot::where('schedule_id', $scheduleId)
                    ->where('game_date', $request->date)
                    ->where('start_time', $request->start_time)
                    ->update([
                        'is_block' => $newBlockStatus,
                        'status'   => 'available',
                    ]);
            } else {
                // Unblocking: keep existing assignments
                $affectedCount = GameSlot::where('schedule_id', $scheduleId)
                    ->where('game_date', $request->date)
                    ->where('start_time', $request->start_time)
                    ->update(['is_block' => $newBlockStatus]);
            }

            DB::commit();

            $message = $newBlockStatus
                ? "{$affectedCount} court(s) blocked and {$totalAssignmentsRemoved} assignment(s) removed."
                : "{$affectedCount} court(s) unblocked successfully.";

            return [
                'success' => true,
                'code'    => 200,
                'message' => $message,
                'data'    => [
                    'date'                 => $request->date,
                    'start_time'           => $request->start_time,
                    'affected_count'       => $affectedCount,
                    'is_blocked'           => $newBlockStatus,
                    'assignments_removed'  => $totalAssignmentsRemoved,
                    'slots'                => $slots->pluck('court_name'),
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to bulk toggle block: ' . $e->getMessage(),
                'data'    => null,
            ];
        }
    }
}
