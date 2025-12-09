<?php

namespace Modules\Director\Http\Controllers\Api\Court;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Director\Models\GameSlot;
use Illuminate\Support\Facades\Validator;

class CourtManageController extends Controller
{
    use ApiResponse;
    /**
     * Block or unblock a game slot
     */
    public function blockUnblockGameSlot(Request $request, $slotId)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:block,unblock',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), null, 422);
        }

        $user = auth('api')->user();

        $slot = GameSlot::with('schedule.camp')->findOrFail($slotId);

        // Check camp director authorization
        if ($slot->schedule->camp->director_id !== $user->id) {
            return $this->error('Unauthorized.', null, 403);
        }

        // Update block status
        $slot->is_block = $request->action === 'block';
        $slot->save();

        return $this->success(
            'Game slot ' . ($slot->is_block ? 'blocked' : 'unblocked') . ' successfully.',
            [
                'slot_id'   => $slot->id,
                'is_block'  => $slot->is_block,
            ],
            200
        );
    }
}
