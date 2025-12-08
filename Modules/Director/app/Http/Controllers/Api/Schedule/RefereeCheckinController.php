<?php

namespace Modules\Director\Http\Controllers\Api\Schedule;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Director\Models\Camp;
use App\Http\Controllers\Controller;
use Modules\Director\Models\CampRefereeCheckin;

class RefereeCheckinController extends Controller
{
    use ApiResponse;

    /**
     * Referee checks in to a camp
     */
    public function checkin(Request $request, $campId)
    {
        $referee = auth('api')->user();

        // Verify camp exists and is active
        $camp = Camp::where('id', $campId)
            ->where('status', 'active')
            ->first();

        if (!$camp) {
            return $this->error('Camp not found or inactive.', null, 404);
        }

        // Check if already checked in
        $existingCheckin = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $referee->id)
            ->first();

        if ($existingCheckin) {
            return $this->error('Already checked in to this camp.', null, 400);
        }

        // Create checkin
        $checkin = CampRefereeCheckin::create([
            'camp_id' => $campId,
            'referee_id' => $referee->id,
            'checked_in_at' => now()
        ]);

        return $this->success(
            'Checked in successfully.',
            [
                'checkin' => $checkin,
                'camp' => [
                    'id' => $camp->id,
                    'name' => $camp->camp_name,
                    'location' => $camp->location,
                    'start_date' => $camp->start_date,
                    'end_date' => $camp->end_date
                ]
            ],
            201
        );
    }

    /**
     * Referee checks out from a camp
     */
    public function checkout($campId)
    {
        $referee = auth('api')->user();

        $checkin = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $referee->id)
            ->first();

        if (!$checkin) {
            return $this->error('Not checked in to this camp.', null, 404);
        }

        $checkin->delete();

        return $this->success('Checked out successfully.', null, 200);
    }

    /**
     * Get list of checked-in referees (Director only)
     */
    public function getCheckedInReferees($campId)
    {
        $user = auth('api')->user();

        // Verify camp ownership
        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $checkins = CampRefereeCheckin::where('camp_id', $campId)
            ->with('referee:id,name,email,phone')
            ->latest('checked_in_at')
            ->get();

        $formatted = $checkins->map(function ($checkin) {
            return [
                'id' => $checkin->id,
                'referee' => $checkin->referee,
                'checked_in_at' => $checkin->checked_in_at->format('Y-m-d H:i:s'),
                'checked_in_ago' => $checkin->checked_in_at->diffForHumans()
            ];
        });

        return $this->success(
            'Checked-in referees fetched successfully.',
            [
                'total_referees' => $formatted->count(),
                'referees' => $formatted
            ],
            200
        );
    }

    /**
     * Get my checked-in camps (Referee)
     */
    public function getMyCheckins()
    {
        $referee = auth('api')->user();

        $checkins = CampRefereeCheckin::where('referee_id', $referee->id)
            ->with('camp:id,camp_name,location,start_date,end_date,camp_logo')
            ->latest('checked_in_at')
            ->get();

        $formatted = $checkins->map(function ($checkin) {
            return [
                'checkin_id' => $checkin->id,
                'camp' => $checkin->camp,
                'checked_in_at' => $checkin->checked_in_at->format('Y-m-d H:i:s')
            ];
        });

        return $this->success(
            'My check-ins fetched successfully.',
            ['checkins' => $formatted],
            200
        );
    }

    /**
     * Bulk checkin referees (Director)
     */
    public function bulkCheckin(Request $request, $campId)
    {
        $user = auth('api')->user();

        $request->validate([
            'referee_ids' => 'required|array',
            'referee_ids.*' => 'exists:users,id'
        ]);

        // Verify camp ownership
        $camp = Camp::where('id', $campId)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $checkedIn = [];
        $alreadyCheckedIn = [];

        foreach ($request->referee_ids as $refereeId) {
            $exists = CampRefereeCheckin::where('camp_id', $campId)
                ->where('referee_id', $refereeId)
                ->exists();

            if ($exists) {
                $alreadyCheckedIn[] = $refereeId;
            } else {
                CampRefereeCheckin::create([
                    'camp_id' => $campId,
                    'referee_id' => $refereeId,
                    'checked_in_at' => now()
                ]);
                $checkedIn[] = $refereeId;
            }
        }

        return $this->success(
            'Bulk check-in completed.',
            [
                'checked_in_count' => count($checkedIn),
                'already_checked_in_count' => count($alreadyCheckedIn),
                'checked_in_ids' => $checkedIn,
                'already_checked_in_ids' => $alreadyCheckedIn
            ],
            200
        );
    }
}
