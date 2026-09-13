<?php

namespace App\Http\Controllers\Web\Backend\V2;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\SportsType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SportsTypeController extends Controller
{
    /**
     * Display a paginated, searchable, and sortable listing of Sports Types.
     */
    public function index(Request $request): Response
    {
        $search    = $request->input('search');
        $status    = $request->input('status');
        $sortBy    = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $perPage   = (int) $request->input('per_page', 10);

        // Allowed sort columns whitelist for security
        $allowedSorts = ['id', 'sports_name', 'sports_fee', 'status', 'camps_count', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        // Optimized query pipeline with camps_count
        $query = SportsType::query()
            ->withCount('camps');

        // Search Filter
        if (!empty($search)) {
            $query->where('sports_name', 'like', '%' . trim($search) . '%');
        }

        // Status Filter
        if (!empty($status) && in_array($status, ['active', 'inactive'])) {
            $query->where('status', $status);
        }

        // Dynamic Sorting
        $query->orderBy($sortBy, $sortOrder);

        // Zero-bloat Paginated Result
        $sportsTypes = $query->paginate($perPage)
            ->withQueryString()
            ->through(function ($item) {
                return [
                    'id'          => $item->id,
                    'sports_name' => $item->sports_name,
                    'sports_fee'  => (float) ($item->sports_fee ?? 0),
                    'icon'        => $item->icon ? asset($item->icon) : null,
                    'raw_icon'    => $item->icon,
                    'status'      => $item->status,
                    'camps_count' => (int) $item->camps_count,
                    'created_at'  => $item->created_at ? $item->created_at->format('M d, Y') : 'N/A',
                ];
            });

        // Overview KPI metrics for the header bar
        $metrics = [
            'total'       => SportsType::count(),
            'active'      => SportsType::where('status', 'active')->count(),
            'inactive'    => SportsType::where('status', 'inactive')->count(),
            'total_camps' => DB::table('camps')->whereNotNull('sports_type_id')->count(),
        ];

        return Inertia::render('SportsType/Index', [
            'sportsTypes' => $sportsTypes,
            'filters'     => [
                'search'     => $search,
                'status'     => $status,
                'sort_by'    => $sortBy,
                'sort_order' => $sortOrder,
                'per_page'   => $perPage,
            ],
            'metrics'     => $metrics,
        ]);
    }

    /**
     * Store a newly created Sports Type.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sports_name' => 'required|string|max:250',
            'sports_fee'  => 'nullable|numeric|min:0|max:99999999.99',
            'icon'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'status'      => 'nullable|in:active,inactive',
        ]);

        try {
            $iconPath = null;
            if ($request->hasFile('icon')) {
                $iconPath = Helper::fileUpload($request->file('icon'), 'sportsType');
            }

            SportsType::create([
                'sports_name' => $validated['sports_name'],
                'sports_fee'  => $validated['sports_fee'] ?? 0,
                'icon'        => $iconPath,
                'status'      => $validated['status'] ?? 'active',
            ]);

            return redirect()->back()->with('success', 'Sports type created successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to create sports type: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing Sports Type.
     */
    public function update(Request $request, $id)
    {
        $sportsType = SportsType::findOrFail($id);

        $validated = $request->validate([
            'sports_name' => 'required|string|max:250',
            'sports_fee'  => 'nullable|numeric|min:0|max:99999999.99',
            'icon'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'status'      => 'nullable|in:active,inactive',
        ]);

        try {
            if ($request->hasFile('icon')) {
                if ($sportsType->icon && file_exists(public_path($sportsType->icon))) {
                    Helper::fileDelete(public_path($sportsType->icon));
                }
                $sportsType->icon = Helper::fileUpload($request->file('icon'), 'sportsType');
            }

            $sportsType->sports_name = $validated['sports_name'];
            $sportsType->sports_fee  = $validated['sports_fee'] ?? $sportsType->sports_fee;
            if (isset($validated['status'])) {
                $sportsType->status = $validated['status'];
            }
            $sportsType->save();

            return redirect()->back()->with('success', 'Sports type updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to update sports type: ' . $e->getMessage());
        }
    }

    /**
     * Toggle status between active and inactive.
     */
    public function toggleStatus($id)
    {
        try {
            $sportsType = SportsType::findOrFail($id);
            $sportsType->status = $sportsType->status === 'active' ? 'inactive' : 'active';
            $sportsType->save();

            return redirect()->back()->with('success', "Status updated to {$sportsType->status}.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to toggle status: ' . $e->getMessage());
        }
    }

    /**
     * Delete a Sports Type with foreign key check.
     */
    public function destroy($id)
    {
        try {
            $sportsType = SportsType::findOrFail($id);

            // Safe guard against deleting sports types linked to active camps
            $hasCamps = DB::table('camps')
                ->where('sports_type_id', $sportsType->id)
                ->exists();

            if ($hasCamps) {
                return redirect()->back()->with('error', 'Cannot delete this sports type because it is linked to one or more camps.');
            }

            if ($sportsType->icon && file_exists(public_path($sportsType->icon))) {
                Helper::fileDelete(public_path($sportsType->icon));
            }

            $sportsType->delete();

            return redirect()->back()->with('success', 'Sports type deleted successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete sports type: ' . $e->getMessage());
        }
    }
}
