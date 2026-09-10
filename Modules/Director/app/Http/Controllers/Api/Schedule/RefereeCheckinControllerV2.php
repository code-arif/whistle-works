<?php

namespace Modules\Director\Http\Controllers\Api\Schedule;

use App\Models\User;
use App\Models\Coupon;
use App\Models\CampPayment;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Director\Models\Camp;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\StripePaymentService;
use Modules\Director\Models\CampRefereeCheckin;

class RefereeCheckinControllerV2 extends Controller
{
    use ApiResponse;

    protected $stripeService;

    public function __construct(StripePaymentService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Get camp details with sports fee breakdown (for referee before applying coupon).
     */
    public function getCampPricing($campId)
    {
        $camp = Camp::with('sportsType')->where('id', $campId)
            ->where('status', 'active')
            ->first();

        if (!$camp) {
            return $this->error('Camp not found or inactive.', null, 404);
        }

        $sportsFee = $camp->sportsType->sports_fee ?? 0;
        $directorPrice = $camp->price - $sportsFee;

        return $this->success(
            'Camp pricing fetched successfully.',
            [
                'camp' => [
                    'id' => $camp->id,
                    'name' => $camp->camp_name,
                    'location' => $camp->location,
                    'start_date' => $camp->start_date->toDateString(),
                    'end_date' => $camp->end_date->toDateString(),
                ],
                'pricing' => [
                    'director_price' => (float) $directorPrice,
                    'sports_fee' => (float) $sportsFee,
                    'total_price' => (float) $camp->price,
                ],
            ],
            200
        );
    }

    /**
     * Validate a coupon code without initiating payment.
     */
    public function validateCoupon(Request $request, $campId)
    {
        $referee = auth('api')->user();

        $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $camp = Camp::where('id', $campId)->where('status', 'active')->first();
        if (!$camp) {
            return $this->error('Camp not found.', null, 404);
        }

        $coupon = Coupon::where('code', $request->coupon_code)->first();

        if (!$coupon) {
            return $this->error('Invalid coupon code.', null, 400);
        }

        // Full validation using new Coupon model methods
        if (!$coupon->isValidForCamp($campId)) {
            return $this->error('Coupon is not valid for this camp or has expired.', null, 400);
        }

        if (!$coupon->isValidForReferee($referee->id)) {
            return $this->error('This coupon is not valid for you.', null, 400);
        }

        if ($coupon->isAlreadyUsedByReferee($referee->id)) {
            return $this->error('You have already used this coupon.', null, 400);
        }

        // Calculate discount
        $sportsFee = $camp->sportsType->sports_fee ?? 0;
        $totalPrice = $camp->price;
        $discountAmount = 0;

        if ($coupon->type === 'fixed') {
            $discountAmount = min($coupon->discount_value, $totalPrice);
        } else {
            $discountAmount = ($totalPrice * $coupon->discount_value) / 100;
        }

        $priceAfterDiscount = max(0, $totalPrice - $discountAmount);

        return $this->success(
            'Coupon is valid.',
            [
                'coupon' => [
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'discount_value' => (float) $coupon->discount_value,
                ],
                'pricing' => [
                    'director_price' => (float) ($totalPrice - $sportsFee),
                    'sports_fee' => (float) $sportsFee,
                    'total_price' => (float) $totalPrice,
                    'discount_amount' => (float) $discountAmount,
                    'price_after_discount' => (float) $priceAfterDiscount,
                ],
            ],
            200
        );
    }

    /**
     * Register for camp V2 (Payment = Registration) with Coupon + Sports Fee support
     */
    public function registerForCamp(Request $request, $campId)
    {
        $referee = auth('api')->user();

        // Verify camp exists and is active
        $camp = Camp::with('sportsType')->where('id', $campId)
            ->where('status', 'active')
            ->first();

        if (!$camp) {
            return $this->error('Camp not found or inactive.', null, 404);
        }

        // Check if already registered
        $existingRegistration = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $referee->id)
            ->first();

        if ($existingRegistration) {
            return $this->error(
                [
                    'registration_status' => $existingRegistration->registration_status,
                    'registered_at' => $existingRegistration->registered_at,
                    'checked_in_at' => $existingRegistration->checked_in_at,
                    'can_check_in' => $existingRegistration->canCheckIn($camp),
                ],
                'Already registered for this camp.',
                400
            );
        }

        // Check if payment already completed (edge case: payment done but registration missing)
        $hasValidPayment = $this->stripeService->hasValidPayment($campId, $referee->id);

        if ($hasValidPayment) {
            // Payment exists but no registration record - create it
            $payment = CampPayment::where('camp_id', $campId)
                ->where('referee_id', $referee->id)
                ->where('status', 'succeeded')
                ->first();

            $registration = CampRefereeCheckin::create([
                'camp_id' => $campId,
                'referee_id' => $referee->id,
                'payment_id' => $payment->id,
                'registration_status' => 'registered',
                'registered_at' => $payment->paid_at,
                'checked_in_at' => null,
            ]);

            return $this->success(
                'Registration completed successfully.',
                [
                    'registration' => [
                        'id' => $registration->id,
                        'status' => $registration->registration_status,
                        'registered_at' => $registration->registered_at,
                        'can_check_in' => $registration->canCheckIn($camp),
                    ],
                    'camp' => [
                        'id' => $camp->id,
                        'name' => $camp->camp_name,
                        'location' => $camp->location,
                        'start_date' => $camp->start_date->toDateString(),
                        'end_date' => $camp->end_date->toDateString(),
                    ],
                    'payment' => [
                        'amount' => $payment->amount,
                        'paid_at' => $payment->paid_at->format('Y-m-d H:i:s'),
                    ]
                ],
                201
            );
        }

        // Handle Coupon — use new per-referee one-time validation
        $coupon = null;
        if ($request->filled('coupon_code')) {
            $coupon = Coupon::where('code', $request->coupon_code)->first();

            if (!$coupon) {
                return $this->error('Invalid coupon code.', null, 400);
            }

            if (!$coupon->isValidForCamp($campId)) {
                return $this->error('Coupon is not valid for this camp or has expired.', null, 400);
            }

            if (!$coupon->isValidForReferee($referee->id)) {
                return $this->error('This coupon is not valid for you.', null, 400);
            }

            if ($coupon->isAlreadyUsedByReferee($referee->id)) {
                return $this->error('You have already used this coupon.', null, 400);
            }
        }

        // Payment not done yet - initiate payment V2
        $paymentResult = $this->stripeService->createCheckoutSessionV2($camp, $referee, $coupon);

        if (!$paymentResult['success']) {
            return $this->error(
                [
                    'message' => $paymentResult['message'] ?? $paymentResult['error'],
                    'details' => $paymentResult['data'] ?? null
                ],
                $paymentResult['error'],
                400
            );
        }

        // Handle free registration (amount < $0.50 threshold bypassed Stripe)
        if (!empty($paymentResult['free_registration'])) {
            $payment = $paymentResult['payment'];

            $registration = CampRefereeCheckin::create([
                'camp_id' => $campId,
                'referee_id' => $referee->id,
                'payment_id' => $payment->id,
                'registration_status' => 'registered',
                'registered_at' => $payment->paid_at,
                'checked_in_at' => null,
            ]);

            return $this->success(
                'Registration completed successfully.',
                [
                    'payment_required' => false,
                    'registration' => [
                        'id' => $registration->id,
                        'status' => $registration->registration_status,
                        'registered_at' => $registration->registered_at->format('Y-m-d H:i:s'),
                        'can_check_in' => $registration->canCheckIn($camp),
                    ],
                    'camp' => [
                        'id' => $camp->id,
                        'name' => $camp->camp_name,
                        'location' => $camp->location,
                        'start_date' => $camp->start_date->toDateString(),
                        'end_date' => $camp->end_date->toDateString(),
                    ],
                    'payment' => [
                        'amount' => $payment->amount,
                        'paid_at' => $payment->paid_at->format('Y-m-d H:i:s'),
                        'discount_amount' => $paymentResult['discount_amount'] ?? 0,
                    ]
                ],
                201
            );
        }

        // Return pricing breakdown + payment URL
        return $this->success(
            'Redirecting to payment...',
            [
                'payment_required' => true,
                'checkout_url' => $paymentResult['checkout_url'],
                'session_id' => $paymentResult['session_id'],
                'expires_at' => $paymentResult['expires_at'],
                'discount' => [
                    'coupon_code' => $coupon?->code,
                    'discount_amount' => $paymentResult['discount_amount'] ?? 0,
                ],
                'price_breakdown' => $paymentResult['amount_breakdown'],
                'camp' => [
                    'id' => $camp->id,
                    'name' => $camp->camp_name,
                    'price' => $camp->price,
                    'location' => $camp->location,
                    'start_date' => $camp->start_date->toDateString(),
                    'end_date' => $camp->end_date->toDateString(),
                ]
            ],
            200
        );
    }
}
