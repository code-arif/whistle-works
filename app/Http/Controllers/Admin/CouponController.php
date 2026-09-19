<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Coupon\StoreCouponRequest;
use App\Http\Requests\Admin\Coupon\UpdateCouponRequest;
use App\Models\Coupon;
use App\Services\Admin\CouponService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function __construct(
        protected CouponService $service
    ) {}

    /**
     * Display a listing of discount coupons.
     */
    public function index(Request $request): Response
    {
        $stats    = $this->service->getStats();
        $coupons  = $this->service->getCoupons($request);
        $formData = $this->service->getFormData();

        return Inertia::render('Coupons/Index', [
            'stats'    => $stats,
            'coupons'  => $coupons,
            'camps'    => $formData['camps'],
            'referees' => $formData['referees'],
            'filters'  => $request->only(['search', 'status', 'type', 'camp_id', 'per_page']),
        ]);
    }

    /**
     * Store a newly created coupon in storage.
     */
    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $this->service->createCoupon($request->validated());

        return redirect()->back()->with('t-success', 'Promotional coupon created successfully.');
    }

    /**
     * Update the specified coupon in storage.
     */
    public function update(UpdateCouponRequest $request, int $id): RedirectResponse
    {
        $coupon = Coupon::findOrFail($id);
        $this->service->updateCoupon($coupon, $request->validated());

        return redirect()->back()->with('t-success', 'Coupon details updated successfully.');
    }

    /**
     * Toggle status of the coupon.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $coupon = Coupon::findOrFail($id);
        $this->service->toggleStatus($coupon);

        return redirect()->back()->with('t-success', 'Coupon status updated successfully.');
    }

    /**
     * Remove the specified coupon from storage.
     */
    public function destroy(int $id): RedirectResponse
    {
        $coupon = Coupon::findOrFail($id);
        $this->service->deleteCoupon($coupon);

        return redirect()->back()->with('t-success', 'Coupon deleted successfully.');
    }
}
