<?php

namespace Modules\Director\Http\Controllers\Api\Camp;

use App\Models\SportsType;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Director\Models\Camp;
use App\Http\Controllers\Controller;
use Modules\Director\Helpers\UploadFile;
use Modules\Director\Transformers\CampResource;
use Modules\Director\Http\Requests\CampCreateRequest;

class CampManageController extends Controller
{
    use ApiResponse;

    // create camp
    public function createCamp(CampCreateRequest $request)
    {
        $user = auth('api')->user();
        $validated = $request->validated();

        // Upload camp logo
        $campLogoPath = null;
        if ($request->hasFile('camp_logo')) {
            $campLogoPath = UploadFile::uploadFiles(
                $request->file('camp_logo'),
                'uploads/camp_logos'
            );
        }

        // Sports Type Validation
        $sportsType = SportsType::find($request->sports_type_id);
        if (!$sportsType) {
            return $this->error('Invalid sports type.', null, 404);
        }

        // Create Camp
        $camp = Camp::create([
            'director_id'      => $user->id,
            'sports_type_id'   => $sportsType->id,
            'sports_type_name' => $sportsType->sports_name,
            'camp_name'        => $request->camp_name,
            'location'         => $request->location,
            'start_date'       => $request->start_date,
            'end_date'         => $request->end_date,
            'camp_details'     => $request->camp_details,
            'price'            => $request->price,
            'camp_logo'        => $campLogoPath,
            'latitude'         => $request->latitude,
            'longitude'        => $request->longitude,
        ]);

        return $this->success(
            'Camp created successfully.',
            new CampResource($camp),
            201
        );
    }


    // Edit Camp
    public function updateCamp(Request $request, $id)
    {
        $user = auth('api')->user();

        // Find Camp
        $camp = Camp::where('id', $id)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        // Validate request fields
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

        // Update Sports Type
        if ($request->filled('sports_type_id')) {
            $sportsType = SportsType::find($request->sports_type_id);
            if ($sportsType) {
                $camp->sports_type_id   = $sportsType->id;
                $camp->sports_type_name = $sportsType->sports_name;
            }
        }

        // Update Logo
        if ($request->hasFile('camp_logo')) {
            if ($camp->camp_logo) {
                UploadFile::deleteImage($camp->camp_logo);
            }
            $camp->camp_logo = UploadFile::uploadFiles(
                $request->file('camp_logo'),
                'uploads/camp_logos'
            );
        }

        // Update dynamic fields
        $fields = ['camp_name', 'location', 'start_date', 'end_date', 'camp_details', 'price'];
        foreach ($fields as $field) {
            if ($request->filled($field)) {
                $camp->$field = $request->$field;
            }
        }

        $camp->save();

        return $this->success(
            'Camp updated successfully.',
            new CampResource($camp),
            200
        );
    }

    /*
     * Update camp status
     */
    public function updateStatus(Request $request, $id)
    {
        $user = auth('api')->user();

        // Validation
        $request->validate([
            'status' => 'required|in:active,inactive'
        ]);

        // Find camp
        $camp = Camp::where('id', $id)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error(null, 'Camp not found.', 404);
        }

        // Update Status
        $camp->status = $request->status;
        $camp->save();

        return $this->success(
            'Camp status updated successfully.',
            new CampResource($camp),
            200
        );
    }

    /**
     * Get camp details
     */
    public function campDetails($id)
    {
        $user = auth('api')->user();

        // Find camp by ID (any user can see — or restrict if needed)
        $camp = Camp::where('id', $id)->with('sportsType')->first();

        if (!$camp) {
            return $this->error(null, 'Camp not found.', 404);
        }

        return $this->success(
            'Camp details fetched successfully.',
            new CampResource($camp),
            200
        );
    }

    /**
     * Delete camp
     */
    public function deleteCamp($id)
    {
        $user = auth('api')->user();

        // Find camp
        $camp = Camp::where('id', $id)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return $this->error(null, 'Camp not found.', 404);
        }

        // Delete camp logo if exists
        if ($camp->camp_logo) {
            UploadFile::deleteImage($camp->camp_logo);
        }

        // Delete camp
        $camp->delete();

        return $this->success(
            'Camp deleted successfully.',
            null,
            200
        );
    }

    /**
     * Director Camp List
     */
    public function directorCampList(Request $request)
    {
        $user = auth('api')->user();

        // Fetch camps created by the logged-in director
        $camps = Camp::where('director_id', $user->id)
            ->with(['sportsType', 'checkedInReferees', 'schedule'])
            ->orderBy('created_at', 'desc')
            ->paginate(8);

        if ($camps->isEmpty()) {
            return $this->error(null, 'No camps found.', 404);
        }

        $response = [
            'camp_list' => $camps->map(function ($camp) {
                return [
                    'camp_id'        => $camp->id,
                    'camp_name'      => $camp->camp_name,
                    'camp_logo'      => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                    'location'       => $camp->location,
                    'sports_type'    => $camp->sportsType->sports_name ?? null,
                    'sports_type_id' => $camp->sports_type_id,
                    'status'         => $camp->status,

                    // totals
                    'total_referees' => $camp->checkedInReferees->count(),
                    'total_courts'    => $camp->schedule ? $camp->schedule->gameSlots()->count() : 0,

                    // metadata
                    'director_id'    => $camp->director_id,
                    'created_at'     => $camp->created_at->format('Y-m-d H:i:s'),
                    'updated_at'     => $camp->updated_at->format('Y-m-d H:i:s'),
                ];
            }),

            'pagination' => [
                'total'         => $camps->total(),
                'per_page'      => $camps->perPage(),
                'current_page'  => $camps->currentPage(),
                'last_page'     => $camps->lastPage(),
            ],
        ];

        return $this->success(
            'Camps fetched successfully.',
            $response,
            200
        );
    }
}
