<?php

namespace App\Services\Admin;

use App\Models\Coupon;
use App\Models\User;
use App\Models\CampPayment;
use Modules\Director\Models\Camp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CouponService
{
    /**
     * Compute KPI statistics for coupons.
     */
    public function getStats(): array
    {
        $totalCoupons = Coupon::count();
        $activeCoupons = Coupon::where('status', 'active')->count();
        $inactiveCoupons = Coupon::where('status', 'inactive')->count();
        $totalUses = (int) Coupon::sum('used_count');

        // Total discount given across all successful camp payments
        $totalDiscountGiven = (float) CampPayment::where('status', 'succeeded')
            ->whereNotNull('coupon_id')
            ->sum('discount_amount');

        // Total revenue generated from transactions using coupons
        $totalRevenueGenerated = (float) CampPayment::where('status', 'succeeded')
            ->whereNotNull('coupon_id')
            ->sum('amount');

        return [
            'totalCoupons'          => $totalCoupons,
            'activeCoupons'         => $activeCoupons,
            'inactiveCoupons'       => $inactiveCoupons,
            'totalUses'             => $totalUses,
            'totalDiscountGiven'    => $totalDiscountGiven,
            'totalRevenueGenerated' => $totalRevenueGenerated,
        ];
    }

    /**
     * Get paginated coupons list with relationships and usage statistics.
     */
    public function getCoupons(Request $request): LengthAwarePaginator
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $type = trim((string) $request->input('type', ''));
        $campId = $request->input('camp_id');
        $perPage = max(10, min(100, (int) $request->input('per_page', 15)));

        $query = Coupon::query()
            ->with([
                'camp:id,camp_name',
                'referees:id,first_name,last_name,email,avatar',
            ])
            ->withCount('usages')
            ->withSum(['campPayments as revenue_generated' => fn($q) => $q->where('status', 'succeeded')], 'amount');

        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($type !== '' && $type !== 'all') {
            $query->where('type', $type);
        }

        if ($campId && $campId !== 'all') {
            $query->where('camp_id', $campId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('camp', fn($cq) => $cq->where('camp_name', 'like', "%{$search}%"))
                  ->orWhereHas('referees', fn($rq) => $rq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();
    }

    /**
     * Get form lookup data (active camps and referees) for creation and edit modals.
     */
    public function getFormData(): array
    {
        $camps = Camp::where('status', 'active')
            ->orderBy('camp_name')
            ->get(['id', 'camp_name']);

        $referees = User::role('referee')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email', 'avatar'])
            ->map(fn($u) => [
                'id'         => $u->id,
                'first_name' => $u->first_name,
                'last_name'  => $u->last_name,
                'name'       => $u->first_name . ' ' . $u->last_name,
                'email'      => $u->email,
                'avatar'     => $u->avatar ? asset($u->avatar) : asset('default/profile.jpg'),
            ]);

        return compact('camps', 'referees');
    }

    /**
     * Create a new coupon.
     */
    public function createCoupon(array $data): Coupon
    {
        $refereeIds = $data['referee_ids'] ?? [];
        unset($data['referee_ids']);

        // Normalize code to uppercase
        $data['code'] = strtoupper(trim($data['code']));

        $coupon = Coupon::create($data);

        if (!empty($refereeIds)) {
            $coupon->referees()->sync($refereeIds);
        }

        return $coupon;
    }

    /**
     * Update an existing coupon.
     */
    public function updateCoupon(Coupon $coupon, array $data): Coupon
    {
        $refereeIds = $data['referee_ids'] ?? [];
        unset($data['referee_ids']);

        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }

        $coupon->update($data);

        if (is_array($refereeIds)) {
            $coupon->referees()->sync($refereeIds);
        }

        return $coupon;
    }

    /**
     * Toggle status between active and inactive.
     */
    public function toggleStatus(Coupon $coupon): Coupon
    {
        $coupon->status = $coupon->status === 'active' ? 'inactive' : 'active';
        $coupon->save();

        return $coupon;
    }

    /**
     * Delete coupon and detach relationships.
     */
    public function deleteCoupon(Coupon $coupon): bool
    {
        $coupon->referees()->detach();
        return (bool) $coupon->delete();
    }
}
