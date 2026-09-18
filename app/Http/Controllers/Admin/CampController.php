<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\SportsType;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Director\Models\Camp;

class CampController extends Controller
{
    /**
     * Display a paginated, searchable, and filterable listing of Camps.
     */
    public function index(Request $request): Response
    {
        $search       = $request->input('search');
        $status       = $request->input('status');
        $sportsTypeId = $request->input('sports_type_id');
        $directorId   = $request->input('director_id');
        $sortBy       = $request->input('sort_by', 'id');
        $sortOrder    = $request->input('sort_order', 'desc');
        $perPage      = (int) $request->input('per_page', 10);

        // Allowed sort columns whitelist
        $allowedSorts = ['id', 'camp_name', 'price', 'start_date', 'end_date', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        // Lean query pipeline with eager loading
        $query = Camp::query()
            ->with([
                'director:id,first_name,last_name,username,email,avatar',
                'sportsType:id,sports_name,sports_fee,icon',
            ]);

        // Search Filter (Camp Name, Location, Address)
        if (!empty($search)) {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('camp_name', 'like', $term)
                  ->orWhere('location', 'like', $term)
                  ->orWhere('address', 'like', $term);
            });
        }

        // Status Filter
        if (!empty($status) && in_array($status, ['active', 'inactive'])) {
            $query->where('status', $status);
        }

        // Sports Type Filter
        if (!empty($sportsTypeId)) {
            $query->where('sports_type_id', $sportsTypeId);
        }

        // Director Filter
        if (!empty($directorId)) {
            $query->where('director_id', $directorId);
        }

        // Dynamic Sorting
        $query->orderBy($sortBy, $sortOrder);

        // Paginated result
        $camps = $query->paginate($perPage)
            ->withQueryString()
            ->through(function ($item) {
                $startDate = $item->start_date ? Carbon::parse($item->start_date) : null;
                $endDate   = $item->end_date ? Carbon::parse($item->end_date) : null;
                $duration  = ($startDate && $endDate) ? $startDate->diffInDays($endDate) + 1 : 1;

                $directorName = 'N/A';
                $directorAvatar = null;
                $directorEmail = '';
                if ($item->director) {
                    $fullName = trim(($item->director->first_name ?? '') . ' ' . ($item->director->last_name ?? ''));
                    $directorName = $fullName ?: ($item->director->username ?? 'Director');
                    $directorAvatar = $item->director->avatar ? asset($item->director->avatar) : null;
                    $directorEmail = $item->director->email ?? '';
                }

                return [
                    'id'               => $item->id,
                    'camp_name'        => $item->camp_name,
                    'location'         => $item->location,
                    'address'          => $item->address,
                    'camp_details'     => $item->camp_details,
                    'start_date'       => $startDate ? $startDate->format('Y-m-d') : null,
                    'end_date'         => $endDate ? $endDate->format('Y-m-d') : null,
                    'formatted_start'  => $startDate ? $startDate->format('M d, Y') : 'TBD',
                    'formatted_end'    => $endDate ? $endDate->format('M d, Y') : 'TBD',
                    'duration_days'    => $duration,
                    'price'            => (float) $item->price,
                    'camp_logo'        => $item->camp_logo ? asset($item->camp_logo) : null,
                    'raw_logo'         => $item->camp_logo,
                    'status'           => $item->status ?? 'inactive',
                    'director_id'      => $item->director_id,
                    'director_name'    => $directorName,
                    'director_avatar'  => $directorAvatar,
                    'director_email'   => $directorEmail,
                    'sports_type_id'   => $item->sports_type_id,
                    'sports_type_name' => $item->sportsType->sports_name ?? ($item->sports_type_name ?? 'N/A'),
                    'sports_type_icon' => $item->sportsType && $item->sportsType->icon ? asset($item->sportsType->icon) : null,
                    'created_at'       => $item->created_at ? $item->created_at->format('M d, Y') : 'N/A',
                ];
            });

        // Overview KPI Metrics
        $today = Carbon::today()->format('Y-m-d');
        $metrics = [
            'total'     => Camp::count(),
            'active'    => Camp::where('status', 'active')->count(),
            'upcoming'  => Camp::where('start_date', '>=', $today)->count(),
            'avg_price' => (float) (Camp::avg('price') ?? 0),
        ];

        // Dropdown Data
        try {
            $directors = User::role('director')
                ->where('status', 'active')
                ->select('id', 'first_name', 'last_name', 'email', 'avatar', 'username')
                ->get()
                ->map(function ($u) {
                    $fullName = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                    return [
                        'id'     => $u->id,
                        'name'   => $fullName ?: ($u->username ?? $u->email),
                        'email'  => $u->email,
                        'avatar' => $u->avatar ? asset($u->avatar) : null,
                    ];
                });
        } catch (Exception $e) {
            $directors = User::select('id', 'first_name', 'last_name', 'email', 'avatar', 'username')
                ->limit(50)
                ->get()
                ->map(function ($u) {
                    $fullName = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                    return [
                        'id'     => $u->id,
                        'name'   => $fullName ?: ($u->username ?? $u->email),
                        'email'  => $u->email,
                        'avatar' => $u->avatar ? asset($u->avatar) : null,
                    ];
                });
        }

        $sportsTypes = SportsType::where('status', 'active')
            ->select('id', 'sports_name', 'sports_fee', 'icon')
            ->get()
            ->map(function ($st) {
                return [
                    'id'         => $st->id,
                    'name'       => $st->sports_name,
                    'sports_fee' => (float) ($st->sports_fee ?? 0),
                    'icon'       => $st->icon ? asset($st->icon) : null,
                ];
            });

        return Inertia::render('Camps/Index', [
            'camps'       => $camps,
            'filters'     => [
                'search'         => $search,
                'status'         => $status,
                'sports_type_id' => $sportsTypeId,
                'director_id'    => $directorId,
                'sort_by'        => $sortBy,
                'sort_order'     => $sortOrder,
                'per_page'       => $perPage,
            ],
            'metrics'     => $metrics,
            'directors'   => $directors,
            'sportsTypes' => $sportsTypes,
        ]);
    }

    /**
     * Store a newly created Camp.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'director_id'    => 'required|exists:users,id',
            'sports_type_id' => 'required|exists:sports_types,id',
            'camp_name'      => 'required|string|max:255',
            'location'       => 'required|string|max:255',
            'address'        => 'nullable|string|max:255',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'camp_details'   => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'camp_logo'      => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'status'         => 'nullable|in:active,inactive',
        ]);

        try {
            $sportsType = SportsType::findOrFail($validated['sports_type_id']);
            $sportsFee  = (float) ($sportsType->sports_fee ?? 0);
            $finalPrice = (float) $validated['price'] + $sportsFee;

            $campLogoPath = null;
            if ($request->hasFile('camp_logo')) {
                $campLogoPath = Helper::fileUpload($request->file('camp_logo'), 'camp_logos');
            }

            Camp::create([
                'director_id'      => $validated['director_id'],
                'sports_type_id'   => $validated['sports_type_id'],
                'sports_type_name' => $sportsType->sports_name,
                'camp_name'        => $validated['camp_name'],
                'location'         => $validated['location'],
                'address'          => $validated['address'] ?? null,
                'start_date'       => $validated['start_date'],
                'end_date'         => $validated['end_date'],
                'camp_details'     => $validated['camp_details'] ?? null,
                'price'            => $finalPrice,
                'camp_logo'        => $campLogoPath,
                'status'           => $validated['status'] ?? 'inactive',
            ]);

            return redirect()->back()->with('success', 'Camp created successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to create camp: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing Camp.
     */
    public function update(Request $request, $id)
    {
        $camp = Camp::findOrFail($id);

        $validated = $request->validate([
            'director_id'    => 'required|exists:users,id',
            'sports_type_id' => 'required|exists:sports_types,id',
            'camp_name'      => 'required|string|max:255',
            'location'       => 'required|string|max:255',
            'address'        => 'nullable|string|max:255',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'camp_details'   => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'camp_logo'      => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'status'         => 'nullable|in:active,inactive',
        ]);

        try {
            $sportsType = SportsType::findOrFail($validated['sports_type_id']);

            if ($request->hasFile('camp_logo')) {
                if ($camp->camp_logo && file_exists(public_path($camp->camp_logo))) {
                    Helper::fileDelete(public_path($camp->camp_logo));
                }
                $camp->camp_logo = Helper::fileUpload($request->file('camp_logo'), 'camp_logos');
            }

            $camp->director_id      = $validated['director_id'];
            $camp->sports_type_id   = $validated['sports_type_id'];
            $camp->sports_type_name = $sportsType->sports_name;
            $camp->camp_name        = $validated['camp_name'];
            $camp->location         = $validated['location'];
            $camp->address          = $validated['address'] ?? $camp->address;
            $camp->start_date       = $validated['start_date'];
            $camp->end_date         = $validated['end_date'];
            $camp->camp_details     = $validated['camp_details'] ?? $camp->camp_details;
            $camp->price            = $validated['price'];
            if (isset($validated['status'])) {
                $camp->status = $validated['status'];
            }
            $camp->save();

            return redirect()->back()->with('success', 'Camp updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to update camp: ' . $e->getMessage());
        }
    }

    /**
     * Toggle status between active and inactive.
     */
    public function toggleStatus($id)
    {
        try {
            $camp = Camp::findOrFail($id);
            $camp->status = $camp->status === 'active' ? 'inactive' : 'active';
            $camp->save();

            return redirect()->back()->with('success', "Camp status updated to {$camp->status}.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to toggle status: ' . $e->getMessage());
        }
    }

    /**
     * Delete a Camp and clean up files.
     */
    public function destroy($id)
    {
        try {
            $camp = Camp::findOrFail($id);

            if ($camp->camp_logo && file_exists(public_path($camp->camp_logo))) {
                Helper::fileDelete(public_path($camp->camp_logo));
            }

            $camp->delete();

            return redirect()->back()->with('success', 'Camp deleted successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete camp: ' . $e->getMessage());
        }
    }
}
