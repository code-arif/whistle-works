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
    public function updateCamp(Request $request, $id)
    {
        $user = auth('api')->user();

        // Find the camp
        $camp = Camp::where('id', $id)->where('director_id', $user->id)->first();
        if (!$camp) {
            return response()->json([
                'status'  => false,
                'message' => 'Camp not found.',
            ], 404);
        }

        // Validate only the fields that exist in the request
        $validated = $request->validate([
            'camp_name'       => 'sometimes|string|max:255',
            'location'        => 'sometimes|string|max:255',
            'start_date'      => 'sometimes|date',
            'end_date'        => 'sometimes|date|after_or_equal:start_date',
            'camp_details'    => 'sometimes|string',
            'price'           => 'sometimes|numeric',
            'sports_type_id'  => 'sometimes|exists:sports_types,id',
            'camp_logo'       => 'sometimes|image|max:2048',
        ]);

        // Update sports type if provided
        if ($request->filled('sports_type_id')) {
            $sportsType = SportsType::find($request->sports_type_id);
            if ($sportsType) {
                $camp->sports_type_id   = $sportsType->id;
                $camp->sports_type_name = $sportsType->sports_name;
            }
        }

        // Update camp logo if uploaded
        if ($request->hasFile('camp_logo')) {
            // Delete old logo if exists
            if ($camp->camp_logo) {
                UploadFile::deleteImage($camp->camp_logo);
            }
            $camp->camp_logo = UploadFile::uploadFiles(
                $request->file('camp_logo'),
                'uploads/camp_logos'
            );
        }

        // Update other fields dynamically if present
        $fields = ['camp_name', 'location', 'start_date', 'end_date', 'camp_details', 'price'];
        foreach ($fields as $field) {
            if ($request->filled($field)) {
                $camp->$field = $request->$field;
            }
        }

        $camp->save();

        return response()->json([
            'status'  => true,
            'message' => 'Camp updated successfully.',
        ], 200);
    }
}
