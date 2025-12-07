<?php

namespace Modules\Director\Http\Controllers\Api\Camp;

use App\Models\SportsType;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Director\Models\Camp;
use App\Http\Controllers\Controller;
use Modules\Director\Helpers\UploadFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Director\Transformers\CampResource;
use Modules\Director\Http\Requests\CampCreateRequest;

class Campcontroller extends Controller
{
    use ApiResponse;
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
     * List camps
     */
    public function campList(Request $request)
    {
        $query = Camp::query()->where('status', 'active');

        // -----------------------------
        // Filter: Sports Type
        // -----------------------------
        if ($request->filled('sports_type_id')) {
            $query->where('sports_type_id', $request->sports_type_id);
        }

        // -----------------------------
        // Filter: Location
        // -----------------------------
        if ($request->filled('location')) {
            $query->where('location', 'LIKE', "%{$request->location}%");
        }

        // -----------------------------
        // Filter: Date Range
        // -----------------------------
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
        }

        // -----------------------------
        // Sorting Type → newest / oldest / upcoming
        // -----------------------------
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'upcoming':
                    $query->where('start_date', '>=', now()->format('Y-m-d'))
                        ->orderBy('start_date', 'asc');
                    break;
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        // -----------------------------
        // Pagination
        // -----------------------------
        $perPage = $request->filled('per_page') ? intval($request->per_page) : 10;
        $camps = $query->paginate($perPage);

        // Custom Pagination Format
        $response = [
            'camp_list'         => CampResource::collection($camps->items()),
            'pagination'   => [
                'total'         => $camps->total(),
                'per_page'      => $camps->perPage(),
                'current_page'  => $camps->currentPage(),
                'last_page'     => $camps->lastPage(),
            ],
        ];

        return $this->success(
            'Camp list fetched successfully.',
            $response,
            200
        );
    }

    /**
     * Get list of locations
     */
    public function getLocations(Request $request)
    {
        // Get all unique first-word locations
        $locations = Camp::selectRaw("DISTINCT SUBSTRING_INDEX(location, ',', 1) as location")
            ->whereNotNull('location')
            ->pluck('location');

        // Manual Pagination
        $perPage = $request->filled('per_page') ? intval($request->per_page) : 10;
        $page = $request->filled('page') ? intval($request->page) : 1;

        $total = $locations->count();
        $results = $locations->slice(($page - 1) * $perPage, $perPage)->values();

        $paginated = new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Custom format for Trait
        $response = [
            'location_list'         => $paginated->items(),
            'pagination'   => [
                'total'         => $paginated->total(),
                'per_page'      => $paginated->perPage(),
                'current_page'  => $paginated->currentPage(),
                'last_page'     => $paginated->lastPage(),
            ],
        ];

        return $this->success(
            'Location list fetched successfully.',
            $response,
            200
        );
    }
}
