<?php

namespace Modules\Director\Http\Controllers\Api\Court;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\Director\Models\GameSlot;
use Modules\Director\Models\Schedule;
use Modules\Director\Models\GameSlotAssignment;

class CourtManageController extends Controller
{
    use ApiResponse;

    /**
     * Toggle block/unblock a court slot
     * When blocking: removes all assignments (crew + individual)
     */
    public function toggleBlockSlot($slotId)
    {
        $user = auth('api')->user();

        $slot = GameSlot::with('schedule.camp')->find($slotId);

        if (!$slot) {
            return $this->error('Game slot not found!', null, 404);
        }

        if ($slot->schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        DB::beginTransaction();
        try {
            $newBlockStatus = !$slot->is_block;

            // If blocking the slot, remove all assignments
            if ($newBlockStatus) {
                $deletedCount = GameSlotAssignment::where('game_slot_id', $slotId)->count();
                GameSlotAssignment::where('game_slot_id', $slotId)->delete();

                // Update slot status to available since assignments are cleared
                $slot->update([
                    'is_block' => $newBlockStatus,
                    'status' => 'available'
                ]);

                DB::commit();

                return $this->success(
                    'Slot blocked successfully. All assignments removed.',
                    [
                        'slot_id' => $slotId,
                        'is_blocked' => $newBlockStatus,
                        'assignments_removed' => $deletedCount
                    ],
                    200
                );
            } else {
                // Just unblock, no assignment changes
                $slot->update(['is_block' => $newBlockStatus]);

                DB::commit();

                return $this->success(
                    'Slot unblocked successfully.',
                    [
                        'slot_id' => $slotId,
                        'is_blocked' => $newBlockStatus
                    ],
                    200
                );
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to toggle block status: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update court name for a specific game slot
     */
    public function updateCourtName(Request $request, $slotId)
    {
        $request->validate([
            'court_name' => 'required|string|max:255'
        ]);

        $user = auth('api')->user();

        $slot = GameSlot::with('schedule.camp')->find($slotId);

        if (!$slot) {
            return $this->error('Game slot not found!', null, 404);
        }

        // Authorization check
        if ($slot->schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        $slot->update(['court_name' => $request->court_name]);

        return $this->success(
            'Court name updated successfully.',
            [
                'slot_id' => $slot->id,
                'court_name' => $slot->court_name
            ],
            200
        );
    }

    /**
     * Block/Unblock all courts at a specific time on a specific date
     * When blocking: removes all assignments from affected slots
     */
    public function bulkToggleBlockByTime(Request $request, $scheduleId)
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'action' => 'required|in:block,unblock'
        ]);

        $user = auth('api')->user();

        // Find schedule and verify ownership
        $schedule = Schedule::with('camp')->findOrFail($scheduleId);

        if ($schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        // Get all slots matching date and time
        $slots = GameSlot::where('schedule_id', $scheduleId)
            ->where('game_date', $request->date)
            ->where('start_time', $request->start_time)
            ->get();

        if ($slots->isEmpty()) {
            return $this->error('No slots found for the given date and time.', null, 404);
        }

        DB::beginTransaction();
        try {
            // Determine new block status
            $newBlockStatus = ($request->action === 'block');
            $slotIds = $slots->pluck('id');

            $totalAssignmentsRemoved = 0;

            // If blocking, remove all assignments first
            if ($newBlockStatus) {
                $totalAssignmentsRemoved = GameSlotAssignment::whereIn('game_slot_id', $slotIds)
                    ->count();

                GameSlotAssignment::whereIn('game_slot_id', $slotIds)->delete();

                // Update all matching slots (block + set to available)
                $affectedCount = GameSlot::where('schedule_id', $scheduleId)
                    ->where('game_date', $request->date)
                    ->where('start_time', $request->start_time)
                    ->update([
                        'is_block' => $newBlockStatus,
                        'status' => 'available'
                    ]);
            } else {
                // Just unblock, keep existing assignments
                $affectedCount = GameSlot::where('schedule_id', $scheduleId)
                    ->where('game_date', $request->date)
                    ->where('start_time', $request->start_time)
                    ->update(['is_block' => $newBlockStatus]);
            }

            DB::commit();

            $message = $newBlockStatus
                ? "{$affectedCount} court(s) blocked and {$totalAssignmentsRemoved} assignment(s) removed."
                : "{$affectedCount} court(s) unblocked successfully.";

            return $this->success(
                $message,
                [
                    'date' => $request->date,
                    'start_time' => $request->start_time,
                    'affected_count' => $affectedCount,
                    'is_blocked' => $newBlockStatus,
                    'assignments_removed' => $totalAssignmentsRemoved,
                    'slots' => $slots->pluck('court_name')
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to bulk toggle block: ' . $e->getMessage(), null, 500);
        }
    }
}
