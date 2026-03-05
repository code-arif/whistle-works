<?php

namespace App\Http\Controllers\Web\Backend\Camp;

use Exception;
use App\Models\User;
use App\Helpers\Helper;
use App\Models\SportsType;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Http\JsonResponse;
use Modules\Director\Models\Camp;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CampController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Camp::with(['director', 'sportsType'])
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('director', function ($data) {
                    if ($data->director) {
                        $fullName = trim($data->director->first_name . ' ' . $data->director->last_name);
                        $avatar = $data->director->avatar
                            ? asset($data->director->avatar)
                            : asset('default/profile.jpg');

                        return '<div class="d-flex align-items-center">
                                    <img src="' . $avatar . '" alt="avatar" width="35" height="35" class="rounded-circle me-2">
                                    <div>
                                        <h6 class="mb-0 fs-14">' . ($fullName ?: $data->director->username) . '</h6>
                                        <small class="text-muted">' . $data->director->email . '</small>
                                    </div>
                                </div>';
                    }
                    return '<span class="text-muted">N/A</span>';
                })
                ->addColumn('camp_info', function ($data) {
                    $logo = $data->camp_logo
                        ? asset($data->camp_logo)
                        : asset('default/no_image.webp');

                    return '<div class="d-flex align-items-center">
                                <img src="' . $logo . '" alt="logo" width="40" height="40" class="rounded me-2">
                                <div>
                                    <h6 class="mb-0 fs-14 fw-semibold">' . $data->camp_name . '<span class="badge bg-primary text-white" style="margin-left: 5px;"> ' . Str::limit($data->address ?? 'N/A', 20) . '</span>' . '</h6>
                                    <small class="text-muted"><i class="fe fe-map-pin"></i> ' .
                        Str::limit($data->location, 30) .
                        '</small>
                                </div>
                        </div>';
                })
                ->addColumn('sports_type', function ($data) {
                    $icon = $data->sportsType && $data->sportsType->icon
                        ? asset($data->sportsType->icon)
                        : asset('default/no_image.webp');

                    return '<div class="d-flex align-items-center">
                                <img src="' . $icon . '" alt="icon" width="30" height="30" class="rounded me-2">
                                <span>' . Str::limit($data->sports_type_name ?? 'N/A', 20) . '</span>
                            </div>';
                })
                ->addColumn('dates', function ($data) {
                    $startDate = \Carbon\Carbon::parse($data->start_date)->format('M d, Y');
                    $endDate = \Carbon\Carbon::parse($data->end_date)->format('M d, Y');
                    $duration = \Carbon\Carbon::parse($data->start_date)->diffInDays($data->end_date) + 1;

                    return '<div>
                                <div class="mb-1"><strong>Start:</strong> ' . $startDate . '</div>
                                <div class="mb-1"><strong>End:</strong> ' . $endDate . '</div>
                                <span class="badge bg-info-light">' . $duration . ' days</span>
                            </div>';
                })
                ->addColumn('price', function ($data) {
                    return '<div class="text-center">
                                <h5 class="mb-0 text-success fw-bold">$' . number_format($data->price, 2) . '</h5>
                            </div>';
                })
                ->addColumn('status', function ($data) {
                    $backgroundColor = $data->status == "active" ? '#4CAF50' : '#ccc';
                    $sliderTranslateX = $data->status == "active" ? '26px' : '2px';
                    $sliderStyles = "position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background-color: white; border-radius: 50%; transition: transform 0.3s ease; transform: translateX($sliderTranslateX);";

                    $status = '<div class="form-check form-switch" style="margin-left:40px; position: relative; width: 50px; height: 24px; background-color: ' . $backgroundColor . '; border-radius: 12px; transition: background-color 0.3s ease; cursor: pointer;">';
                    $status .= '<input onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" class="form-check-input" id="customSwitch' . $data->id . '" getAreaid="' . $data->id . '" name="status" style="position: absolute; width: 100%; height: 100%; opacity: 0; z-index: 2; cursor: pointer;">';
                    $status .= '<span style="' . $sliderStyles . '"></span>';
                    $status .= '<label for="customSwitch' . $data->id . '" class="form-check-label" style="margin-left: 10px;"></label>';
                    $status .= '</div>';

                    return $status;
                })
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
                                <a href="#" type="button" onclick="showViewModal(' . $data->id . ')" class="btn btn-success fs-14 text-white" title="View">
                                    <i class="fe fe-eye"></i>
                                </a>
                                <a href="#" type="button" onclick="showEditModal(' . $data->id . ')" class="btn btn-primary fs-14 text-white" title="Edit">
                                    <i class="fe fe-edit"></i>
                                </a>
                                <a href="#" type="button" onclick="showDeleteConfirm(' . $data->id . ')" class="btn btn-danger fs-14 text-white" title="Delete">
                                    <i class="fe fe-trash"></i>
                                </a>
                            </div>';
                })
                ->rawColumns(['director', 'camp_info', 'sports_type', 'dates', 'price', 'status', 'action'])
                ->make();
        }

        // Get directors and sports types for dropdowns
        $directors = User::role('director')->where('status', 'active')->get();
        $sportsTypes = SportsType::where('status', 'active')->get();

        return view("backend.layouts.camps.index", compact('directors', 'sportsTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'director_id'      => 'required|exists:users,id',
            'sports_type_id'   => 'required|exists:sports_types,id',
            'camp_name'        => 'required|max:255',
            'location'         => 'required|max:255',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
            'camp_details'     => 'nullable|string',
            'price'            => 'required|numeric',
            'camp_logo'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',
            'address'          => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 't-error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Get sports type name
            $sportsType = SportsType::findOrFail($data['sports_type_id']);

            // Add extra price (from env or default $25)
            $extraPrice = env('CAMP_EXTRA_PRICE', 25);
            $finalPrice = $data['price'] + $extraPrice;

            // Handle camp logo upload
            $campLogoPath = null;
            if ($request->hasFile('camp_logo')) {
                $campLogoPath = Helper::fileUpload(
                    $request->file('camp_logo'),
                    'camp_logos',
                    time() . '_' . getFileName($request->file('camp_logo'))
                );
            }

            // Create camp
            $camp = Camp::create([
                'director_id'      => $data['director_id'],
                'sports_type_id'   => $data['sports_type_id'],
                'sports_type_name' => $sportsType->sports_name,
                'camp_name'        => $data['camp_name'],
                'location'         => $data['location'],
                'start_date'       => $data['start_date'],
                'end_date'         => $data['end_date'],
                'camp_details'     => $data['camp_details'] ?? null,
                'price'            => $finalPrice,
                'camp_logo'        => $campLogoPath,
                'latitude'         => $data['latitude'] ?? null,
                'longitude'        => $data['longitude'] ?? null,
                'status'           => 'inactive', // Default status
                'address'          => $data['address'] ?? null,
            ]);

            return response()->json([
                'status' => 't-success',
                'message' => 'Camp created successfully! (Extra $' . $extraPrice . ' added to price)',
                'data' => $camp
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 't-error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $camp = Camp::with(['director', 'sportsType'])->findOrFail($id);

            return response()->json([
                'status' => 't-success',
                'data' => $camp
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 't-error',
                'message' => 'Camp not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'director_id'      => 'required|exists:users,id',
            'sports_type_id'   => 'required|exists:sports_types,id',
            'camp_name'        => 'required|max:255',
            'location'         => 'required|max:255',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
            'camp_details'     => 'nullable|string',
            'price'            => 'required|numeric',
            'camp_logo'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',
            'address'          => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 't-error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $camp = Camp::findOrFail($id);
            $data = $validator->validated();

            // Get sports type name
            $sportsType = SportsType::findOrFail($data['sports_type_id']);

            // Handle camp logo upload
            if ($request->hasFile('camp_logo')) {
                // Delete old logo if exists
                if ($camp->camp_logo && file_exists(public_path($camp->camp_logo))) {
                    Helper::fileDelete(public_path($camp->camp_logo));
                }

                $data['camp_logo'] = Helper::fileUpload(
                    $request->file('camp_logo'),
                    'camp_logos',
                    time() . '_' . getFileName($request->file('camp_logo'))
                );
            }

            // Update camp
            $camp->update([
                'director_id'      => $data['director_id'],
                'sports_type_id'   => $data['sports_type_id'],
                'sports_type_name' => $sportsType->sports_name,
                'camp_name'        => $data['camp_name'],
                'location'         => $data['location'],
                'start_date'       => $data['start_date'],
                'end_date'         => $data['end_date'],
                'camp_details'     => $data['camp_details'] ?? $camp->camp_details,
                'price'            => $data['price'],
                'camp_logo'        => $data['camp_logo'] ?? $camp->camp_logo,
                'latitude'         => $data['latitude'] ?? $camp->latitude,
                'longitude'        => $data['longitude'] ?? $camp->longitude,
                'address'          => $data['address'] ?? $camp->address,
            ]);

            return response()->json([
                'status' => 't-success',
                'message' => 'Camp updated successfully',
                'data' => $camp
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 't-error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $camp = Camp::findOrFail($id);

            // Delete camp logo if exists
            if ($camp->camp_logo && file_exists(public_path($camp->camp_logo))) {
                Helper::fileDelete(public_path($camp->camp_logo));
            }

            $camp->delete();

            return response()->json([
                'status' => 't-success',
                'message' => 'Camp deleted successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 't-error',
                'message' => 'Failed to delete camp'
            ], 500);
        }
    }

    /**
     * Update camp status
     */
    public function status($id): JsonResponse
    {
        try {
            $camp = Camp::findOrFail($id);

            $camp->status = $camp->status === 'active' ? 'inactive' : 'active';
            $camp->save();

            return response()->json([
                'status' => 't-success',
                'message' => 'Camp status updated successfully!',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 't-error',
                'message' => 'Failed to update status',
            ], 500);
        }
    }
}
