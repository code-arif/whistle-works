<?php

namespace App\Http\Controllers\Web\Backend\Coupon;

use Exception;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Director\Models\Camp;

class CouponController extends Controller
{
    /**
     * Display a listing of the coupons.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Coupon::query()
                ->with(['camp', 'director', 'referees'])
                ->withCount('usages')
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('type', function ($data) {
                    $badgeClass = $data->type === 'percentage' ? 'badge bg-info' : 'badge bg-primary';
                    $label = $data->type === 'percentage' ? $data->discount_value . '%' : '$' . number_format($data->discount_value, 2);
                    return '<span class="' . $badgeClass . '">' . $label . '</span>';
                })
                ->addColumn('usage', function ($data) {
                    $max = $data->max_uses ?? '∞';
                    $used = $data->used_count;
                    return $used . ' / ' . $max;
                })
                ->addColumn('camp', function ($data) {
                    return $data->camp ? $data->camp->camp_name : '<span class="text-muted">All Camps</span>';
                })
                ->addColumn('scope', function ($data) {
                    $scopes = [];
                    if ($data->referees->isNotEmpty()) {
                        $names = $data->referees->take(3)->map(fn($r) => $r->first_name . ' ' . $r->last_name)->implode(', ');
                        $count = $data->referees->count();
                        $label = $count > 3 ? $names . ' <span class="badge p-2 bg-secondary">+' . ($count - 3) . '</span>' : $names;
                        $scopes[] = '<span class="badge p-2 bg-warning text-dark" title="' . $count . ' referee(s)">Referees: ' . $label . '</span>';
                    }
                    if ($data->camp_id) {
                        $scopes[] = '<span class="badge bg-info">Camp: ' . ($data->camp->camp_name ?? 'N/A') . '</span>';
                    }
                    if (empty($scopes)) {
                        return '<span class="badge bg-secondary">Global</span>';
                    }
                    return implode(' ', $scopes);
                })
                ->addColumn('expires_at', function ($data) {
                    return $data->expires_at ? $data->expires_at->format('Y-m-d') : '<span class="text-muted">Never</span>';
                })
                ->addColumn('status', function ($data) {
                    $backgroundColor = $data->status === 'active' ? '#4CAF50' : '#ccc';
                    $sliderTranslateX = $data->status === 'active' ? '26px' : '2px';
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
                                <a href="javascript:void(0)" onclick="openEditModal(' . $data->id . ')" class="btn btn-primary fs-14 text-white" title="Edit">
                                    <i class="fe fe-edit"></i>
                                </a>
                                <a href="javascript:void(0)" onclick="showDeleteConfirm(' . $data->id . ')" class="btn btn-danger fs-14 text-white" title="Delete">
                                    <i class="fe fe-trash"></i>
                                </a>
                            </div>';
                })
                ->rawColumns(['type', 'expires_at', 'status', 'action', 'camp', 'scope'])
                ->make();
        }

        $camps = Camp::where('status', 'active')->orderBy('camp_name')->get(['id', 'camp_name']);
        $referees = User::role('referee')->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'email']);

        return view('backend.layouts.coupon.index', compact('camps', 'referees'));
    }

    /**
     * Store a newly created coupon in storage (AJAX).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code'           => 'required|string|max:50|unique:coupons,code',
            'type'           => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
            'max_uses'       => 'nullable|integer|min:1',
            'camp_id'        => 'nullable|exists:camps,id',
            'referee_ids'    => 'nullable|array',
            'referee_ids.*'  => 'exists:users,id',
            'expires_at'     => 'nullable|date',
            'status'         => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 't-error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $refereeIds = $data['referee_ids'] ?? [];
            unset($data['referee_ids']);

            // Create coupon with per-referee one-time tracking
            // If camp_id is set: coupon works only for that camp
            // If referees are assigned: only those referees can use it (each one-time)
            $coupon = Coupon::create($data);

            // Sync the pivot for multiple referees
            if (!empty($refereeIds)) {
                $coupon->referees()->sync($refereeIds);
            }

            return response()->json([
                'status'  => 't-success',
                'message' => 'Coupon created successfully! Each referee can use it only once.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 't-error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return coupon data as JSON for the edit modal.
     */
    public function getCoupon($id): JsonResponse
    {
        try {
            $coupon = Coupon::with('referees')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'             => $coupon->id,
                    'code'           => $coupon->code,
                    'type'           => $coupon->type,
                    'discount_value' => $coupon->discount_value,
                    'max_uses'       => $coupon->max_uses,
                    'used_count'     => $coupon->used_count,
                    'camp_id'        => $coupon->camp_id,
                    'director_id'    => $coupon->director_id,
                    'referee_ids'    => $coupon->referees->pluck('id')->toArray(),
                    'expires_at'     => $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : null,
                    'status'         => $coupon->status,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found.',
            ], 404);
        }
    }

    /**
     * Update the specified coupon in storage (AJAX).
     */
    public function update(Request $request, $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code'           => 'required|string|max:50|unique:coupons,code,' . $id,
            'type'           => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
            'max_uses'       => 'nullable|integer|min:1',
            'camp_id'        => 'nullable|exists:camps,id',
            'referee_ids'    => 'nullable|array',
            'referee_ids.*'  => 'exists:users,id',
            'expires_at'     => 'nullable|date',
            'status'         => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 't-error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $refereeIds = $data['referee_ids'] ?? [];
            unset($data['referee_ids']);

            $coupon->update($data);

            // Sync the pivot for multiple referees
            if (!empty($refereeIds)) {
                $coupon->referees()->sync($refereeIds);
            } else {
                $coupon->referees()->detach();
            }

            return response()->json([
                'status'  => 't-success',
                'message' => 'Coupon updated successfully!',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 't-error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified coupon from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $coupon = Coupon::findOrFail($id);
            $coupon->delete();

            return response()->json([
                'status'  => 't-success',
                'message' => 'Coupon deleted successfully!',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 't-error',
                'message' => 'Failed to delete coupon.',
            ], 500);
        }
    }

    /**
     * Toggle coupon status.
     */
    public function status(int $id): JsonResponse
    {
        $data = Coupon::findOrFail($id);
        $data->status = $data->status === 'active' ? 'inactive' : 'active';
        $data->save();

        return response()->json([
            'status'  => 't-success',
            'message' => 'Status updated successfully!',
        ]);
    }
}
