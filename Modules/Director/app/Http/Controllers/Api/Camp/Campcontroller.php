<?php

namespace Modules\Director\Http\Controllers\Api\Camp;

use App\Models\SportsType;
use Illuminate\Http\Request;
use Modules\Director\Models\Camp;
use App\Http\Controllers\Controller;
use Modules\Director\Helpers\UploadFile;
use Modules\Director\Http\Requests\CampCreateRequest;

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

    // create camp

    public function createCamp(CampCreateRequest $request)
    {
        $user = auth('api')->user();
        $validated = $request->validated();
        // Upload logo using helper
        $campLogoPath = null;
        if ($request->hasFile('camp_logo')) {
            $campLogoPath = UploadFile::uploadFiles(
                $request->file('camp_logo'),
                'uploads/camp_logos'  
            );
        }

        // Sports Type
        $sportsType = SportsType::find($request->sports_type_id);
        if (!$sportsType) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid sports type.',
            ], 404);
        }

        $sprotsTypeName = SportsType::find($request->sports_type_id)->sports_name;
        
        $camp = Camp::create([
            'director_id'      => $user->id,
            'sports_type_id'   => $request->sports_type_id,
            'sports_type_name' => $sprotsTypeName,
            'camp_name'        => $request->camp_name,
            'location'         => $request->location,
            'start_date'       => $request->start_date,
            'end_date'         => $request->end_date,
            'camp_details'     => $request->camp_details,
            'price'            => $request->price,
            'camp_logo'        => $campLogoPath,
        ]);

        return response()->json([
            'status' => 'true',
            'message' => 'Camp created successfully.',
        ], 201);
    }

    // Edit Camp
    public function editCamp(Request $request, $id)
    {
       $authUser = auth('api')->user();
       $camp = Camp::where('id', $id)->where('director_id', $authUser->id)->first();
       if (!$camp) {
           return response()->json([
               'status'  => false,
               'message' => 'Camp not found or you do not have permission to edit this camp.',
           ], 404);
       }

       
    }
}
