<?php

namespace App\Services\Admin;

use App\Helpers\HandlesTimezones;
use App\Helpers\Helper;
use App\Models\SportsType;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Director\Models\Camp;

class CampService
{
    /**
     * Get paginated, searchable, and filtered Camps listing.
     */
    public function getCamps(Request $request): LengthAwarePaginator
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

        return $query->paginate($perPage)
            ->withQueryString()
            ->through(function ($item) {
                $startDate = $item->start_date ? Carbon::parse($item->start_date) : null;
                $endDate   = $item->end_date ? Carbon::parse($item->end_date) : null;
                $duration  = ($startDate && $endDate) ? $startDate->diffInDays($endDate) + 1 : 1;

                $directorName   = 'N/A';
                $directorAvatar = null;
                $directorEmail  = '';
                if ($item->director) {
                    $fullName       = trim(($item->director->first_name ?? '') . ' ' . ($item->director->last_name ?? ''));
                    $directorName   = $fullName ?: ($item->director->username ?? 'Director');
                    $directorAvatar = $item->director->avatar ? asset($item->director->avatar) : null;
                    $directorEmail  = $item->director->email ?? '';
                }

                return [
                    'id'               => $item->id,
                    'camp_name'        => $item->camp_name,
                    'location'         => $item->location,
                    'address'          => $item->address,
                    'latitude'         => $item->latitude ? (float) $item->latitude : null,
                    'longitude'        => $item->longitude ? (float) $item->longitude : null,
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
    }

    /**
     * Compute overview KPI metrics for camps.
     */
    public function getMetrics(): array
    {
        $today = Carbon::today()->format('Y-m-d');

        return [
            'total'     => Camp::count(),
            'active'    => Camp::where('status', 'active')->count(),
            'upcoming'  => Camp::where('start_date', '>=', $today)->count(),
            'avg_price' => (float) (Camp::avg('price') ?? 0),
        ];
    }

    /**
     * Get active directors for dropdown selector.
     */
    public function getDirectors(): Collection
    {
        try {
            return User::role('director')
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
            return User::select('id', 'first_name', 'last_name', 'email', 'avatar', 'username')
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
    }

    /**
     * Get active sports types for dropdown selector.
     */
    public function getSportsTypes(): Collection
    {
        return SportsType::where('status', 'active')
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
    }

    /**
     * Extract filter state array from request.
     */
    public function getFilters(Request $request): array
    {
        $sortBy    = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSorts = ['id', 'camp_name', 'price', 'start_date', 'end_date', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        return [
            'search'         => $request->input('search'),
            'status'         => $request->input('status'),
            'sports_type_id' => $request->input('sports_type_id'),
            'director_id'    => $request->input('director_id'),
            'sort_by'        => $sortBy,
            'sort_order'     => strtolower($sortOrder) === 'asc' ? 'asc' : 'desc',
            'per_page'       => (int) $request->input('per_page', 10),
        ];
    }

    /**
     * Create a new Camp.
     */
    public function createCamp(array $data, $logoFile = null): Camp
    {
        $sportsType = SportsType::findOrFail($data['sports_type_id']);
        $sportsFee  = (float) ($sportsType->sports_fee ?? 0);
        $finalPrice = (float) $data['price'] + $sportsFee;

        $campLogoPath = null;
        if ($logoFile) {
            $campLogoPath = Helper::fileUpload($logoFile, 'camp_logos');
        }

        $timezone = null;
        if (!empty($data['latitude']) && !empty($data['longitude'])) {
            $timezone = HandlesTimezones::detectFromCoordinates($data['latitude'], $data['longitude']);
        }

        return Camp::create([
            'director_id'      => $data['director_id'],
            'sports_type_id'   => $data['sports_type_id'],
            'sports_type_name' => $sportsType->sports_name,
            'camp_name'        => $data['camp_name'],
            'location'         => $data['location'],
            'address'          => $data['address'] ?? null,
            'latitude'         => $data['latitude'] ?? null,
            'longitude'        => $data['longitude'] ?? null,
            'timezone'         => $timezone,
            'start_date'       => $data['start_date'],
            'end_date'         => $data['end_date'],
            'camp_details'     => $data['camp_details'] ?? null,
            'price'            => $finalPrice,
            'camp_logo'        => $campLogoPath,
            'status'           => $data['status'] ?? 'inactive',
        ]);
    }

    /**
     * Update an existing Camp.
     */
    public function updateCamp(Camp $camp, array $data, $logoFile = null): Camp
    {
        $sportsType = SportsType::findOrFail($data['sports_type_id']);

        if ($logoFile) {
            if ($camp->camp_logo && file_exists(public_path($camp->camp_logo))) {
                Helper::fileDelete(public_path($camp->camp_logo));
            }
            $camp->camp_logo = Helper::fileUpload($logoFile, 'camp_logos');
        }

        $camp->director_id      = $data['director_id'];
        $camp->sports_type_id   = $data['sports_type_id'];
        $camp->sports_type_name = $sportsType->sports_name;
        $camp->camp_name        = $data['camp_name'];
        $camp->location         = $data['location'];
        $camp->address          = $data['address'] ?? $camp->address;

        if (array_key_exists('latitude', $data)) {
            $camp->latitude = $data['latitude'];
        }
        if (array_key_exists('longitude', $data)) {
            $camp->longitude = $data['longitude'];
        }

        if ($camp->latitude && $camp->longitude) {
            $camp->timezone = HandlesTimezones::detectFromCoordinates($camp->latitude, $camp->longitude);
        }

        $camp->start_date   = $data['start_date'];
        $camp->end_date     = $data['end_date'];
        $camp->camp_details = $data['camp_details'] ?? $camp->camp_details;
        $camp->price        = $data['price'];

        if (isset($data['status'])) {
            $camp->status = $data['status'];
        }

        $camp->save();

        return $camp;
    }

    /**
     * Toggle Camp status between active and inactive.
     */
    public function toggleStatus(Camp $camp): string
    {
        $camp->status = $camp->status === 'active' ? 'inactive' : 'active';
        $camp->save();

        return $camp->status;
    }

    /**
     * Delete a Camp and clean up associated assets.
     */
    public function deleteCamp(Camp $camp): bool
    {
        if ($camp->camp_logo && file_exists(public_path($camp->camp_logo))) {
            Helper::fileDelete(public_path($camp->camp_logo));
        }

        return (bool) $camp->delete();
    }
}
