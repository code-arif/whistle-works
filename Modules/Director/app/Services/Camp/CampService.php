<?php

namespace Modules\Director\Services\Camp;

use Carbon\Carbon;
use App\Models\SportsType;
use App\Helpers\HandlesTimezones;
use App\Services\LocationService;
use Modules\Director\Models\Camp;
use Modules\Director\Helpers\UploadFile;
use Modules\Director\Transformers\CampResource;
use Modules\Director\Transformers\CampEditResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\User;

class CampService
{
    protected LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Create a new camp with timezone support.
     *
     * @param  User              $user
     * @param  array             $validated
     * @param  UploadedFile|null $logoFile
     * @return array
     */
    public function createCamp(User $user, array $validated, ?UploadedFile $logoFile = null): array
    {
        // Upload camp logo
        $campLogoPath = null;
        if ($logoFile) {
            $campLogoPath = UploadFile::uploadFiles($logoFile, 'uploads/camp_logos');
        }

        // Sports Type Validation
        $sportsType = SportsType::find($validated['sports_type_id']);
        if (!$sportsType) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Invalid sports type.',
                'data'    => null,
            ];
        }

        // Use sports_type.sports_fee instead of global CAMP_EXTRA_PRICE
        $sportsFee = $sportsType->sports_fee ?? 0;

        // Final price calculation: base price + sports fee (admin fee)
        $finalPrice = $validated['price'] + $sportsFee;

        // Detect timezone from coordinates if not provided
        $timezone  = $validated['timezone'] ?? null;
        $latitude  = $validated['latitude'] ?? null;
        $longitude = $validated['longitude'] ?? null;

        if (!$timezone && $latitude && $longitude) {
            $timezone = HandlesTimezones::detectFromCoordinates($latitude, $longitude);
        }

        // Handle Location Name
        $locationName = $validated['location'] ?? null;

        // If coordinates provided, fetch proper location name
        if ($latitude && $longitude) {
            // Validate coordinates
            if (!$this->locationService->validateCoordinates($latitude, $longitude)) {
                return [
                    'success' => false,
                    'code'    => 400,
                    'message' => 'Invalid coordinates provided.',
                    'data'    => null,
                ];
            }

            // Fetch location from Google
            $locationResult = $this->locationService->getLocationFromCoordinates(
                $latitude,
                $longitude,
                'detailed' // Options: 'short', 'detailed', 'address', 'full'
            );

            if ($locationResult['success']) {
                // Use Google's location if:
                // 1. No location name provided from frontend, OR
                // 2. Frontend location name is too long (> 100 chars)
                if (empty($locationName) || strlen($locationName) > 100) {
                    $locationName = $locationResult['location_name'];
                }
            } else {
                // Google fetch failed, use fallback
                if (empty($locationName)) {
                    $locationName = 'Location coordinates: ' . $latitude . ', ' . $longitude;
                }
            }
        } else {
            // No coordinates provided, location name is required
            if (empty($locationName)) {
                return [
                    'success' => false,
                    'code'    => 400,
                    'message' => 'Location name or coordinates are required.',
                    'data'    => null,
                ];
            }
        }

        // CRITICAL: Parse dates in the camp's timezone and store as date-only format
        $campTimezone = $timezone ?? config('app.timezone', 'UTC');

        $startDate = Carbon::parse($validated['start_date'], $campTimezone)->format('Y-m-d');
        $endDate   = Carbon::parse($validated['end_date'], $campTimezone)->format('Y-m-d');

        // Create Camp
        $camp = Camp::create([
            'director_id'      => $user->id,
            'sports_type_id'   => $sportsType->id,
            'sports_type_name' => $sportsType->sports_name,
            'camp_name'        => $validated['camp_name'],
            'location'         => $locationName,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'camp_details'     => $validated['camp_details'] ?? null,
            'price'            => $finalPrice,
            'camp_logo'        => $campLogoPath,
            'latitude'         => $latitude,
            'longitude'        => $longitude,
            'timezone'         => $campTimezone,
            'status'           => 'inactive',
            'address'          => $validated['address'] ?? null,
        ]);

        return [
            'success' => true,
            'code'    => 201,
            'message' => 'Camp created successfully.',
            'data'    => new CampResource($camp),
        ];
    }

    /**
     * Update an existing camp.
     *
     * @param  User    $user
     * @param  int     $id
     * @param  Request $request
     * @return array
     */
    public function updateCamp(User $user, int $id, Request $request): array
    {
        // Find Camp
        $camp = Camp::where('id', $id)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        // Validate request fields
        $request->validate([
            'camp_name'       => 'sometimes|string|max:255',
            'location'        => 'sometimes|string|max:255',
            'start_date'      => 'sometimes|date',
            'end_date'        => 'sometimes|date|after_or_equal:start_date',
            'camp_details'    => 'sometimes|string',
            'price'           => 'sometimes|numeric',
            'sports_type_id'  => 'sometimes|exists:sports_types,id',
            'camp_logo'       => 'sometimes|image|max:2048',
            'latitude'        => 'sometimes|numeric',
            'longitude'       => 'sometimes|numeric',
            'timezone'        => 'sometimes|string|timezone',
            'address'         => 'sometimes|string|max:255',
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

        /**
         * Handle Coordinates, Location & Timezone
         */
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $latitude  = $request->latitude;
            $longitude = $request->longitude;

            if (!$this->locationService->validateCoordinates($latitude, $longitude)) {
                return [
                    'success' => false,
                    'code'    => 400,
                    'message' => 'Invalid coordinates provided.',
                    'data'    => null,
                ];
            }

            $camp->latitude  = $latitude;
            $camp->longitude = $longitude;

            // Auto update location if not provided
            if (!$request->filled('location')) {
                $locationResult = $this->locationService->getLocationFromCoordinates(
                    $latitude,
                    $longitude,
                    'detailed'
                );

                if ($locationResult['success']) {
                    $camp->location = $locationResult['location_name'];
                }
            }

            // Auto detect timezone if not provided
            if (!$request->filled('timezone')) {
                $camp->timezone = HandlesTimezones::detectFromCoordinates($latitude, $longitude);
            }
        }

        // Explicit location update
        if ($request->filled('location')) {
            $locationName = $request->location;

            if (strlen($locationName) > 100 && $camp->latitude && $camp->longitude) {
                $locationResult = $this->locationService->getLocationFromCoordinates(
                    $camp->latitude,
                    $camp->longitude,
                    'detailed'
                );

                if ($locationResult['success']) {
                    $locationName = $locationResult['location_name'];
                }
            }

            $camp->location = $locationName;
        }

        // Address update
        if ($request->filled('address')) {
            $camp->address = $request->address;
        }

        /**
         * CRITICAL: Price handling — uses sports_type.sports_fee instead of global CAMP_EXTRA_PRICE
         */
        if ($request->filled('price')) {
            $sportsFee  = $camp->sportsType->sports_fee ?? 0;
            $camp->price = $request->price + $sportsFee;
        }

        /**
         * Date handling (timezone-safe, date only)
         */
        $campTimezone = $request->timezone ?? $camp->timezone ?? config('app.timezone');

        if ($request->filled('start_date')) {
            $camp->start_date = Carbon::parse(
                $request->start_date,
                $campTimezone
            )->format('Y-m-d');
        }

        if ($request->filled('end_date')) {
            $camp->end_date = Carbon::parse(
                $request->end_date,
                $campTimezone
            )->format('Y-m-d');
        }

        /**
         * Update remaining simple fields
         */
        $fields = ['camp_name', 'camp_details', 'timezone'];
        foreach ($fields as $field) {
            if ($request->filled($field)) {
                $camp->$field = $request->$field;
            }
        }

        $camp->save();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Camp updated successfully.',
            'data'    => new CampResource($camp),
        ];
    }

    /**
     * Update camp status.
     *
     * @param  User   $user
     * @param  int    $id
     * @param  string $status
     * @return array
     */
    public function updateStatus(User $user, int $id, string $status): array
    {
        $camp = Camp::where('id', $id)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        $camp->status = $status;
        $camp->save();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Camp status updated successfully.',
            'data'    => new CampResource($camp),
        ];
    }

    /**
     * Get camp details (auth — with role-based eager loading).
     *
     * @param  User $user
     * @param  int  $id
     * @return array
     */
    public function campDetails(User $user, int $id): array
    {
        $camp = Camp::with(['sportsType', 'assistantDirectorPermissions' => function ($query) use ($user) {
            $query->where('assistant_director_id', $user->id);
        }])
            ->when($user->hasRole('referee'), function ($query) use ($user) {
                $query->with(['checkedInReferees' => function ($q) use ($user) {
                    $q->where('referee_id', $user->id);
                }]);
            })
            ->when($user->hasRole('evaluator'), function ($query) use ($user) {
                $query->with(['evaluatorRegistrations' => function ($q) use ($user) {
                    $q->where('evaluator_id', $user->id);
                }]);
            })
            ->find($id);

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Camp details fetched successfully.',
            'data'    => new CampResource($camp),
        ];
    }

    /**
     * Get camp details without authentication.
     *
     * @param  int $id
     * @return array
     */
    public function noAuthCampDetails(int $id): array
    {
        $camp = Camp::with(['sportsType', 'director'])->find($id);

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Camp details fetched successfully.',
            'data'    => new CampResource($camp),
        ];
    }

    /**
     * Delete a camp and its logo file.
     *
     * @param  User $user
     * @param  int  $id
     * @return array
     */
    public function deleteCamp(User $user, int $id): array
    {
        $camp = Camp::where('id', $id)
            ->where('director_id', $user->id)
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        // Delete camp logo if exists
        if ($camp->camp_logo) {
            UploadFile::deleteImage($camp->camp_logo);
        }

        $camp->delete();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Camp deleted successfully.',
            'data'    => null,
        ];
    }

    /**
     * Get paginated list of camps owned by the director.
     *
     * @param  User $user
     * @param  int  $perPage
     * @return array
     */
    public function directorCampList(User $user, int $perPage = 8): array
    {
        $today = now()->toDateString();

        $camps = Camp::where('director_id', $user->id)
            ->with(['sportsType', 'checkedInReferees', 'schedule'])
            ->where('end_date', '>', $today)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $response = [
            'camp_list' => $camps->map(function ($camp) {
                return [
                    'camp_id'        => $camp->id,
                    'camp_name'      => $camp->camp_name,
                    'camp_logo'      => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                    'location'       => $camp->location,
                    'timezone'       => $camp->timezone,
                    'timezone_name'  => $camp->timezone_display_name,
                    'sports_type'    => $camp->sportsType->sports_name ?? null,
                    'sports_type_id' => $camp->sports_type_id,
                    'status'         => $camp->status,

                    // totals
                    'total_referees' => $camp->checkedInReferees->count(),
                    'total_courts'   => $camp->schedule ? $camp->schedule->gameSlots()->count() : 0,

                    // metadata
                    'director_id'    => $camp->director_id,
                    'created_at'     => $camp->created_at->format('Y-m-d H:i:s'),
                    'updated_at'     => $camp->updated_at->format('Y-m-d H:i:s'),
                ];
            }),

            'pagination' => [
                'total'        => $camps->total(),
                'per_page'     => $camps->perPage(),
                'current_page' => $camps->currentPage(),
                'last_page'    => $camps->lastPage(),
            ],
        ];

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Camps fetched successfully.',
            'data'    => $response,
        ];
    }

    /**
     * Get admin/sports fees (list of all active sports types with their fees).
     *
     * @return array
     */
    public function getAdminFee(): array
    {
        // Returns a list of sports fees since each sport has its own fee
        $sportsFees = SportsType::where('status', 'active')
            ->get(['id', 'sports_name', 'sports_fee']);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Sports fees fetched successfully.',
            'data'    => ['sports_fees' => $sportsFees],
        ];
    }

    /**
     * Get all available timezones formatted for a dropdown.
     *
     * @return array
     */
    public function getAvailableTimezones(): array
    {
        $timezones = HandlesTimezones::getAllTimezones();

        $formatted = collect($timezones)->map(function ($name, $value) {
            return [
                'value'  => $value,
                'label'  => $name,
                'offset' => Carbon::now($value)->offsetHours,
            ];
        })->values();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Timezones fetched successfully.',
            'data'    => ['timezones' => $formatted],
        ];
    }

    /**
     * Get camp details for editing (auth — with role-based eager loading).
     *
     * @param  User $user
     * @param  int  $id
     * @return array
     */
    public function campEdit(User $user, int $id): array
    {
        $camp = Camp::with(['sportsType'])
            ->when($user->hasRole('referee'), function ($query) use ($user) {
                $query->with(['checkedInReferees' => function ($q) use ($user) {
                    $q->where('referee_id', $user->id);
                }]);
            })
            ->when($user->hasRole('evaluator'), function ($query) use ($user) {
                $query->with(['evaluatorRegistrations' => function ($q) use ($user) {
                    $q->where('evaluator_id', $user->id);
                }]);
            })
            ->find($id);

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Camp details fetched successfully.',
            'data'    => new CampEditResource($camp),
        ];
    }

    // -------------------------------------------------------------------------
    // NoAuth Methods (no authentication required)
    // -------------------------------------------------------------------------

    /**
     * Get all active sports types.
     *
     * @return array
     */
    public function getSportsType(): array
    {
        $sportsTypes = SportsType::where('status', 'active')->get();

        if ($sportsTypes->isEmpty()) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'No active sports types found.',
                'data'    => [],
            ];
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Active sports types retrieved successfully.',
            'data'    => $sportsTypes,
        ];
    }

    /**
     * Get a filtered, sorted, and paginated public camp list.
     *
     * @param  Request $request
     * @return array
     */
    public function campList(Request $request): array
    {
        $query = Camp::query()->where('status', 'active');

        // Filter: Sports Type
        if ($request->filled('sports_type_id')) {
            $query->where('sports_type_id', $request->sports_type_id);
        }

        // Filter: Location
        if ($request->filled('location')) {
            $query->where('location', 'LIKE', "%{$request->location}%");
        }

        // Filter: Date Range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
        }

        // Sorting: newest / oldest / upcoming
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

        $perPage = $request->filled('per_page') ? intval($request->per_page) : 10;
        $camps   = $query->paginate($perPage);

        $response = [
            'camp_list'  => CampResource::collection($camps->items()),
            'pagination' => [
                'total'        => $camps->total(),
                'per_page'     => $camps->perPage(),
                'current_page' => $camps->currentPage(),
                'last_page'    => $camps->lastPage(),
            ],
        ];

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Camp list fetched successfully.',
            'data'    => $response,
        ];
    }

    /**
     * Get a paginated list of unique camp locations.
     *
     * @param  Request $request
     * @return array
     */
    public function getLocations(Request $request): array
    {
        // Get all unique first-word locations
        $locations = Camp::selectRaw("DISTINCT SUBSTRING_INDEX(location, ',', 1) as location")
            ->whereNotNull('location')
            ->pluck('location');

        // Manual Pagination
        $perPage = $request->filled('per_page') ? intval($request->per_page) : 10;
        $page    = $request->filled('page') ? intval($request->page) : 1;

        $total   = $locations->count();
        $results = $locations->slice(($page - 1) * $perPage, $perPage)->values();

        $paginated = new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $response = [
            'location_list' => $paginated->items(),
            'pagination'    => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
        ];

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Location list fetched successfully.',
            'data'    => $response,
        ];
    }
}
