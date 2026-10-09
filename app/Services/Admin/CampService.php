<?php

namespace App\Services\Admin;

use App\Helpers\HandlesTimezones;
use App\Helpers\Helper;
use App\Models\CampEvaluatorRegistration;
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
        $timezone     = $request->input('timezone');
        $sortBy       = $request->input('sort_by', 'id');
        $sortOrder    = $request->input('sort_order', 'desc');
        $perPage      = (int) $request->input('per_page', 10);

        // Allowed sort columns whitelist
        $allowedSorts = ['id', 'camp_name', 'price', 'start_date', 'end_date', 'status', 'created_at', 'timezone'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        $query = Camp::query()
            ->with([
                'director:id,first_name,last_name,username,email,avatar',
                'sportsType:id,sports_name,sports_fee,icon',
                'schedule:id,camp_id,mode,status',
            ])
            ->withCount([
                'checkedInReferees',
                'evaluatorRegistrations',
                'crews',
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

        // Timezone Filter
        if (!empty($timezone)) {
            $query->where('timezone', $timezone);
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

                $sportsFee  = (float) ($item->sportsType->sports_fee ?? 0);
                $totalPrice = (float) $item->price;
                $basePrice  = max(0, round($totalPrice - $sportsFee, 2));

                $campTimezone = $item->timezone ?: config('app.timezone', 'UTC');
                $timezoneDisplayName = HandlesTimezones::getDisplayName($campTimezone);
                $offsetHours = 0;
                try {
                    $offsetHours = Carbon::now($campTimezone)->offsetHours;
                } catch (Exception $e) {
                    $offsetHours = 0;
                }
                $offsetSign = $offsetHours >= 0 ? '+' : '';

                return [
                    'id'                                 => $item->id,
                    'camp_name'                          => $item->camp_name,
                    'location'                           => $item->location,
                    'address'                            => $item->address,
                    'latitude'                           => $item->latitude ? (float) $item->latitude : null,
                    'longitude'                          => $item->longitude ? (float) $item->longitude : null,
                    'timezone'                           => $campTimezone,
                    'timezone_display_name'              => $timezoneDisplayName,
                    'timezone_offset'                    => "UTC{$offsetSign}{$offsetHours}",
                    'timezone_offset_hours'              => $offsetHours,
                    'camp_details'                       => $item->camp_details,
                    'start_date'                         => $startDate ? $startDate->format('Y-m-d') : null,
                    'end_date'                           => $endDate ? $endDate->format('Y-m-d') : null,
                    'formatted_start'                    => $startDate ? $startDate->format('M d, Y') : 'TBD',
                    'formatted_end'                      => $endDate ? $endDate->format('M d, Y') : 'TBD',
                    'duration_days'                      => $duration,
                    'base_price'                         => $basePrice,
                    'sports_fee'                         => $sportsFee,
                    'total_price'                        => $totalPrice,
                    'price'                              => $totalPrice,
                    'camp_logo'                          => $item->camp_logo ? asset($item->camp_logo) : null,
                    'raw_logo'                           => $item->camp_logo,
                    'status'                             => $item->status ?? 'inactive',
                    'publish_ranking_for_evaluators'     => (bool) ($item->publish_ranking_for_evaluators ?? true),
                    'hide_evaluator_name_from_referees'   => (bool) ($item->hide_evaluator_name_from_referees ?? false),
                    'hide_ranking_numbers_from_referees'  => (bool) ($item->hide_ranking_numbers_from_referees ?? false),
                    'publish_ranking_for_referees'       => (bool) ($item->publish_ranking_for_referees ?? false),
                    'checked_in_referees_count'          => (int) ($item->checked_in_referees_count ?? 0),
                    'evaluator_registrations_count'      => (int) ($item->evaluator_registrations_count ?? 0),
                    'crews_count'                        => (int) ($item->crews_count ?? 0),
                    'has_schedule'                       => (bool) $item->schedule,
                    'schedule_status'                    => $item->schedule->status ?? null,
                    'director_id'                        => $item->director_id,
                    'director_name'                      => $directorName,
                    'director_avatar'                    => $directorAvatar,
                    'director_email'                     => $directorEmail,
                    'sports_type_id'                     => $item->sports_type_id,
                    'sports_type_name'                   => $item->sportsType->sports_name ?? ($item->sports_type_name ?? 'N/A'),
                    'sports_type_icon'                   => $item->sportsType && $item->sportsType->icon ? asset($item->sportsType->icon) : null,
                    'created_at'                         => $item->created_at ? $item->created_at->format('M d, Y') : 'N/A',
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
     * Get all available timezones formatted with labels and UTC offsets.
     */
    public function getTimezones(): array
    {
        $all = HandlesTimezones::getAllTimezones();
        $formatted = [];

        foreach ($all as $value => $label) {
            try {
                $offset = Carbon::now($value)->offsetHours;
            } catch (Exception $e) {
                $offset = 0;
            }
            $sign = $offset >= 0 ? '+' : '';
            $formatted[] = [
                'value'       => $value,
                'label'       => "{$label} (UTC{$sign}{$offset})",
                'name'        => $label,
                'offset'      => $offset,
                'offset_text' => "UTC{$sign}{$offset}",
            ];
        }

        return $formatted;
    }

    /**
     * Extract filter state array from request.
     */
    public function getFilters(Request $request): array
    {
        $sortBy    = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSorts = ['id', 'camp_name', 'price', 'start_date', 'end_date', 'status', 'created_at', 'timezone'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        return [
            'search'         => $request->input('search'),
            'status'         => $request->input('status'),
            'sports_type_id' => $request->input('sports_type_id'),
            'director_id'    => $request->input('director_id'),
            'timezone'       => $request->input('timezone'),
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

        // Determine timezone: explicit input > auto-detected from coordinates > default UTC
        $timezone = !empty($data['timezone']) ? $data['timezone'] : null;
        if (!$timezone && !empty($data['latitude']) && !empty($data['longitude'])) {
            $timezone = HandlesTimezones::detectFromCoordinates((float) $data['latitude'], (float) $data['longitude']);
        }
        if (!$timezone) {
            $timezone = config('app.timezone', 'UTC');
        }

        // Parse dates in camp's timezone
        $startDate = Carbon::parse($data['start_date'], $timezone)->format('Y-m-d');
        $endDate   = Carbon::parse($data['end_date'], $timezone)->format('Y-m-d');

        return Camp::create([
            'director_id'                        => $data['director_id'],
            'sports_type_id'                     => $data['sports_type_id'],
            'sports_type_name'                   => $sportsType->sports_name,
            'camp_name'                          => $data['camp_name'],
            'location'                           => $data['location'],
            'address'                            => $data['address'] ?? null,
            'latitude'                           => $data['latitude'] ?? null,
            'longitude'                          => $data['longitude'] ?? null,
            'timezone'                           => $timezone,
            'start_date'                         => $startDate,
            'end_date'                           => $endDate,
            'camp_details'                       => $data['camp_details'] ?? null,
            'price'                              => $finalPrice,
            'camp_logo'                          => $campLogoPath,
            'status'                             => $data['status'] ?? 'active',
            'publish_ranking_for_evaluators'     => $data['publish_ranking_for_evaluators'] ?? true,
            'hide_evaluator_name_from_referees'   => $data['hide_evaluator_name_from_referees'] ?? false,
            'hide_ranking_numbers_from_referees'  => $data['hide_ranking_numbers_from_referees'] ?? false,
            'publish_ranking_for_referees'       => $data['publish_ranking_for_referees'] ?? false,
        ]);
    }

    /**
     * Update an existing Camp.
     */
    public function updateCamp(Camp $camp, array $data, $logoFile = null): Camp
    {
        $sportsType = SportsType::findOrFail($data['sports_type_id']);
        $sportsFee  = (float) ($sportsType->sports_fee ?? 0);

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

        // Timezone: prioritize explicit input, fallback to auto-detect if coords provided
        if (!empty($data['timezone'])) {
            $camp->timezone = $data['timezone'];
        } elseif ($camp->latitude && $camp->longitude) {
            $camp->timezone = HandlesTimezones::detectFromCoordinates((float) $camp->latitude, (float) $camp->longitude);
        }

        $campTimezone = $camp->timezone ?: config('app.timezone', 'UTC');

        // Parse dates in camp's timezone
        $camp->start_date = Carbon::parse($data['start_date'], $campTimezone)->format('Y-m-d');
        $camp->end_date   = Carbon::parse($data['end_date'], $campTimezone)->format('Y-m-d');

        $camp->camp_details = $data['camp_details'] ?? $camp->camp_details;

        // Base registration fee + sport administrative fee = total stored price
        $camp->price = (float) $data['price'] + $sportsFee;

        if (isset($data['status'])) {
            $camp->status = $data['status'];
        }

        if (array_key_exists('publish_ranking_for_evaluators', $data)) {
            $camp->publish_ranking_for_evaluators = (bool) $data['publish_ranking_for_evaluators'];
            CampEvaluatorRegistration::where('camp_id', $camp->id)
                ->where('status', 'approved')
                ->update([
                    'can_view_own_evaluations' => (bool) $data['publish_ranking_for_evaluators'],
                ]);
        }
        if (array_key_exists('hide_evaluator_name_from_referees', $data)) {
            $camp->hide_evaluator_name_from_referees = (bool) $data['hide_evaluator_name_from_referees'];
        }
        if (array_key_exists('hide_ranking_numbers_from_referees', $data)) {
            $camp->hide_ranking_numbers_from_referees = (bool) $data['hide_ranking_numbers_from_referees'];
        }
        if (array_key_exists('publish_ranking_for_referees', $data)) {
            $camp->publish_ranking_for_referees = (bool) $data['publish_ranking_for_referees'];
        }

        $camp->save();

        return $camp;
    }

    /**
     * Duplicate an existing Camp.
     */
    public function duplicateCamp(Camp $camp): Camp
    {
        $newCamp = $camp->replicate([
            'id',
            'created_at',
            'updated_at',
        ]);
        $newCamp->camp_name = $camp->camp_name . ' (Copy)';
        $newCamp->status = 'inactive';
        $newCamp->save();

        return $newCamp;
    }

    /**
     * Quick update of ranking and evaluation privacy settings.
     */
    public function updateRankingSettings(Camp $camp, array $data): Camp
    {
        if (array_key_exists('publish_ranking_for_evaluators', $data)) {
            $camp->publish_ranking_for_evaluators = filter_var($data['publish_ranking_for_evaluators'], FILTER_VALIDATE_BOOLEAN);
            CampEvaluatorRegistration::where('camp_id', $camp->id)
                ->where('status', 'approved')
                ->update([
                    'can_view_own_evaluations' => $camp->publish_ranking_for_evaluators,
                ]);
        }
        if (array_key_exists('hide_evaluator_name_from_referees', $data)) {
            $camp->hide_evaluator_name_from_referees = filter_var($data['hide_evaluator_name_from_referees'], FILTER_VALIDATE_BOOLEAN);
        }
        if (array_key_exists('hide_ranking_numbers_from_referees', $data)) {
            $camp->hide_ranking_numbers_from_referees = filter_var($data['hide_ranking_numbers_from_referees'], FILTER_VALIDATE_BOOLEAN);
        }
        if (array_key_exists('publish_ranking_for_referees', $data)) {
            $camp->publish_ranking_for_referees = filter_var($data['publish_ranking_for_referees'], FILTER_VALIDATE_BOOLEAN);
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
