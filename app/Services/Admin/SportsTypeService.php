<?php

namespace App\Services\Admin;

use App\Helpers\Helper;
use App\Models\SportsType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SportsTypeService
{
    /**
     * Get paginated, searchable, and sorted Sports Types listing.
     */
    public function getSportsTypes(Request $request): LengthAwarePaginator
    {
        $search    = $request->input('search');
        $status    = $request->input('status');
        $sortBy    = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $perPage   = (int) $request->input('per_page', 10);

        // Allowed sort columns whitelist
        $allowedSorts = ['id', 'sports_name', 'sports_fee', 'status', 'camps_count', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        $query = SportsType::query()
            ->with(['levels'])
            ->withCount('camps');

        if (!empty($search)) {
            $query->where('sports_name', 'like', '%' . trim($search) . '%');
        }

        if (!empty($status) && in_array($status, ['active', 'inactive'])) {
            $query->where('status', $status);
        }

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage)
            ->withQueryString()
            ->through(function ($item) {
                return [
                    'id'           => $item->id,
                    'sports_name'  => $item->sports_name,
                    'sports_fee'   => (float) ($item->sports_fee ?? 0),
                    'icon'         => $item->icon ? asset($item->icon) : null,
                    'raw_icon'     => $item->icon,
                    'status'       => $item->status,
                    'camps_count'  => (int) $item->camps_count,
                    'levels_count' => (int) $item->levels->count(),
                    'levels'       => $item->levels->map(function ($lvl) {
                        return [
                            'id'         => $lvl->id,
                            'name'       => $lvl->name,
                            'sort_order' => (int) $lvl->sort_order,
                            'status'     => $lvl->status,
                        ];
                    })->values()->toArray(),
                    'created_at'   => $item->created_at ? $item->created_at->format('M d, Y') : 'N/A',
                ];
            });
    }

    /**
     * Compute overview KPI metrics for the header cards.
     */
    public function getMetrics(): array
    {
        return [
            'total'       => SportsType::count(),
            'active'      => SportsType::where('status', 'active')->count(),
            'inactive'    => SportsType::where('status', 'inactive')->count(),
            'total_camps' => DB::table('camps')->whereNotNull('sports_type_id')->count(),
        ];
    }

    /**
     * Extract filter state array from request.
     */
    public function getFilters(Request $request): array
    {
        $sortBy    = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSorts = ['id', 'sports_name', 'sports_fee', 'status', 'camps_count', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        return [
            'search'     => $request->input('search'),
            'status'     => $request->input('status'),
            'sort_by'    => $sortBy,
            'sort_order' => strtolower($sortOrder) === 'asc' ? 'asc' : 'desc',
            'per_page'   => (int) $request->input('per_page', 10),
        ];
    }

    /**
     * Create a new Sports Type.
     */
    public function createSportsType(array $data, $iconFile = null): SportsType
    {
        $iconPath = null;
        if ($iconFile) {
            $iconPath = Helper::fileUpload($iconFile, 'sportsType');
        }

        $sportsType = SportsType::create([
            'sports_name' => $data['sports_name'],
            'sports_fee'  => $data['sports_fee'] ?? 0,
            'icon'        => $iconPath,
            'status'      => $data['status'] ?? 'active',
        ]);

        // Process dynamic evaluation levels
        $this->syncEvaluationLevels($sportsType, $data['evaluation_levels'] ?? null);

        return $sportsType;
    }

    /**
     * Update an existing Sports Type.
     */
    public function updateSportsType(SportsType $sportsType, array $data, $iconFile = null): SportsType
    {
        if ($iconFile) {
            if ($sportsType->icon && file_exists(public_path($sportsType->icon))) {
                Helper::fileDelete(public_path($sportsType->icon));
            }
            $sportsType->icon = Helper::fileUpload($iconFile, 'sportsType');
        }

        $sportsType->sports_name = $data['sports_name'];
        $sportsType->sports_fee  = $data['sports_fee'] ?? $sportsType->sports_fee;

        if (isset($data['status'])) {
            $sportsType->status = $data['status'];
        }

        $sportsType->save();

        // Process dynamic evaluation levels
        if (array_key_exists('evaluation_levels', $data)) {
            $this->syncEvaluationLevels($sportsType, $data['evaluation_levels']);
        }

        return $sportsType;
    }

    /**
     * Synchronize evaluation levels for a sports type.
     */
    protected function syncEvaluationLevels(SportsType $sportsType, mixed $rawLevels): void
    {
        if (empty($rawLevels)) {
            return;
        }

        // Parse if JSON string from FormData
        $levels = is_string($rawLevels) ? json_decode($rawLevels, true) : $rawLevels;

        if (!is_array($levels)) {
            return;
        }

        $incomingIds = [];
        $order = 1;

        foreach ($levels as $levelData) {
            $name = is_array($levelData) ? trim($levelData['name'] ?? '') : trim((string) $levelData);
            if (empty($name)) {
                continue;
            }

            $id = is_array($levelData) ? ($levelData['id'] ?? null) : null;
            $sortOrder = is_array($levelData) && isset($levelData['sort_order']) ? (int) $levelData['sort_order'] : $order;
            $status = is_array($levelData) && isset($levelData['status']) ? $levelData['status'] : 'active';

            if ($id) {
                $existing = $sportsType->levels()->where('id', $id)->first();
                if ($existing) {
                    $existing->update([
                        'name'       => $name,
                        'sort_order' => $sortOrder,
                        'status'     => $status,
                    ]);
                    $incomingIds[] = $existing->id;
                    $order++;
                    continue;
                }
            }

            // Create new level
            $newLevel = $sportsType->levels()->create([
                'name'       => $name,
                'sort_order' => $sortOrder,
                'status'     => $status,
            ]);
            $incomingIds[] = $newLevel->id;
            $order++;
        }

        // Remove deleted levels if any
        if (!empty($incomingIds)) {
            $sportsType->levels()->whereNotIn('id', $incomingIds)->delete();
        }
    }

    /**
     * Toggle status between active and inactive.
     */
    public function toggleStatus(SportsType $sportsType): string
    {
        $sportsType->status = $sportsType->status === 'active' ? 'inactive' : 'active';
        $sportsType->save();

        return $sportsType->status;
    }

    /**
     * Delete a Sports Type with foreign key check.
     */
    public function deleteSportsType(SportsType $sportsType): array
    {
        $hasCamps = DB::table('camps')
            ->where('sports_type_id', $sportsType->id)
            ->exists();

        if ($hasCamps) {
            return [
                'success' => false,
                'message' => 'Cannot delete this sports type because it is linked to one or more camps.',
            ];
        }

        if ($sportsType->icon && file_exists(public_path($sportsType->icon))) {
            Helper::fileDelete(public_path($sportsType->icon));
        }

        $sportsType->delete();

        return [
            'success' => true,
            'message' => 'Sports type deleted successfully.',
        ];
    }
}
