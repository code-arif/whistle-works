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
                'camp' => [
                    'id' => $checkin->camp->id,
                    'camp_name' => $checkin->camp->camp_name,
                    'location' => $checkin->camp->location,
                    'camp_logo' => $checkin->camp->camp_logo ? asset($checkin->camp->camp_logo) : asset('default/no_image.webp'),
                    'start_date' => $checkin->camp->start_date,
                    'end_date' => $checkin->camp->end_date
                ],
                'checked_in_at' => $checkin->checked_in_at->format('Y-m-d H:i:s')
            ];
        });

        return $this->success(
            'My check-ins fetched successfully.',
            ['checkins' => $formatted],
            200
        );
    }
}
