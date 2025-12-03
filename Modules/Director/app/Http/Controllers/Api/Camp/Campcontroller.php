<?php

namespace Modules\Director\Http\Controllers\Api\Camp;

use App\Http\Controllers\Controller;
use App\Models\SportsType;
use Illuminate\Http\Request;

class Campcontroller extends Controller
{
    public function getSportsType()
    {
        $sportsTypes = SportsType::where('status', 'active')->get();

        if ($sportsTypes->isEmpty()) {
            return response()->json([
                'message' => 'No active sports types found.'
            ], 404);
        }

        return response()->json([
            'status' => 'true',
            'message' => 'Active sports types retrieved successfully.',
            'data' => $sportsTypes
        ], 200);
    }
}
