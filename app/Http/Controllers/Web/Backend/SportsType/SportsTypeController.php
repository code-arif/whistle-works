<?php

namespace App\Http\Controllers\Web\Backend\SportsType;

use Exception;
use App\Helpers\Helper;
use App\Models\SportsType;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SportsTypeController extends Controller
{
    /**
     * Display the index page with DataTable.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = SportsType::withCount('camps')->orderBy('id', 'desc')->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('camps_count', function ($data) {
                    $count = $data->camps_count;
                    $badgeClass = $count > 0 ? 'bg-primary' : 'bg-secondary';
                    return '<span class="badge ' . $badgeClass . ' rounded-pill fs-12 p-3">' . $count . '</span>';
                })
                ->addColumn('icon', function ($data) {
                    if ($data->icon) {
                        $url = asset($data->icon);
                        return '<img src="' . $url . '" alt="icon" width="50px" height="50px" style="margin-left:20px;">';
                    } else {
                        return '<img src="' . asset('default/no_image.webp') . '" alt="icon" width="50px" height="50px" style="margin-left:20px;">';
                    }
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
                                <a href="#" type="button" onclick="openEditModal(' . $data->id . ')" class="btn btn-primary fs-14 text-white delete-icn" title="Edit">
                                    <i class="fe fe-edit"></i>
                                </a>
                                <a href="#" type="button" onclick="showDeleteConfirm(' . $data->id . ')" class="btn btn-danger fs-14 text-white delete-icn" title="Delete">
                                    <i class="fe fe-trash"></i>
                                </a>
                            </div>';
                })
                ->rawColumns(['camps_count', 'status', 'action', 'icon'])
                ->make();
        }
        return view("backend.layouts.sportsType.index");
    }

    /**
     * Store a newly created sports type via AJAX.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sports_name' => 'required|max:250',
            'sports_fee'  => 'nullable|numeric|min:0|max:99999999.99',
            'icon'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'errors'  => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('icon')) {
                $data['icon'] = Helper::fileUpload($request->file('icon'), 'sportsType', time() . '_' . getFileName($request->file('icon')));
            }

            $sportsType = SportsType::create([
                'sports_name' => $data['sports_name'],
                'sports_fee'  => $data['sports_fee'] ?? 0,
                'icon'        => $data['icon'] ?? null,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Sports type created successfully.',
                'data'    => $sportsType,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified sports type via AJAX.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sports_name' => 'required|max:250',
            'sports_fee'  => 'nullable|numeric|min:0|max:99999999.99',
            'icon'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'errors'  => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $sportsType = SportsType::findOrFail($id);

            if ($request->hasFile('icon')) {
                $data['icon'] = Helper::fileUpload($request->file('icon'), 'sportsType', time() . '_' . getFileName($request->file('icon')));
            }

            $sportsType->sports_name = $data['sports_name'];
            $sportsType->sports_fee = $data['sports_fee'] ?? $sportsType->sports_fee;
            $sportsType->icon = $data['icon'] ?? $sportsType->icon;
            $sportsType->save();

            return response()->json([
                'status'  => 'success',
                'message' => 'Sports type updated successfully.',
                'data'    => $sportsType->fresh(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single sports type for editing (AJAX).
     */
    public function getSportsType($id): JsonResponse
    {
        try {
            $sportsType = SportsType::findOrFail($id);
            return response()->json([
                'status' => 'success',
                'data'   => $sportsType,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Sports type not found.',
            ], 404);
        }
    }

    /**
     * Remove the specified sports type via AJAX.
     * Blocks deletion if the sports type has associated camps.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $data = SportsType::findOrFail($id);

            // Check if any camps are associated with this sports type
            $hasCamps = DB::table('camps')
                ->where('sports_type_id', $data->id)
                ->exists();

            if ($hasCamps) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Cannot delete this sports type because it has associated camps. Please remove or reassign the camps first.',
                ], 400);
            }

            if ($data->icon && file_exists(public_path($data->icon))) {
                Helper::fileDelete(public_path($data->icon));
            }

            $data->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Sports type deleted successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle sports type status via AJAX.
     */
    public function status(int $id): JsonResponse
    {
        try {
            $data = SportsType::findOrFail($id);
            $data->status = $data->status === 'active' ? 'inactive' : 'active';
            $data->save();

            return response()->json([
                'status'  => 'success',
                'message' => 'Status updated successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Item not found.',
            ], 404);
        }
    }
}
