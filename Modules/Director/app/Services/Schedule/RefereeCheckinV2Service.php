<?php

namespace Modules\Director\Services\Schedule;

use App\Models\CampPayment;
use App\Models\Coupon;
use App\Services\StripePaymentService;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;

class RefereeCheckinV2Service
{
    protected StripePaymentService $stripeService;

    public function __construct(StripePaymentService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Get camp details with sports fee breakdown (V2).
     *
     * @param  int $campId
     * @return array
     */
    public function getCampPricing(int $campId): array
    {
        $camp = Camp::with('sportsType')->where('id', $campId)
            ->where('status', 'active')
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found or inactive.',
                'data'    => null,
            ];
        }

        $sportsFee     = $camp->sportsType->sports_fee ?? 0;
        $directorPrice = $camp->price - $sportsFee;

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Camp pricing fetched successfully.',
            'data'    => [
                'camp'    => [
                    'id'         => $camp->id,
                    'name'       => $camp->camp_name,
                    'location'   => $camp->location,
                    'start_date' => $camp->start_date->toDateString(),
                    'end_date'   => $camp->end_date->toDateString(),
                ],
                'pricing' => [
                    'director_price' => (float) $directorPrice,
                    'sports_fee'     => (float) $sportsFee,
                    'total_price'    => (float) $camp->price,
                ],
            ],
        ];
    }

    /**
     * Validate a coupon code without initiating payment (V2).
     *
     * @param  mixed  $referee
     * @param  int    $campId
     * @param  string $couponCode
     * @return array
     */
    public function validateCoupon($referee, int $campId, string $couponCode): array
    {
        $camp = Camp::where('id', $campId)->where('status', 'active')->first();
        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        $coupon = Coupon::where('code', $couponCode)->first();

        if (!$coupon) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Invalid coupon code.',
                'data'    => null,
            ];
        }

        if (!$coupon->isValidForCamp($campId)) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Coupon is not valid for this camp or has expired.',
                'data'    => null,
            ];
        }

        if (!$coupon->isValidForReferee($referee->id)) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'This coupon is not valid for you.',
                'data'    => null,
            ];
        }

        if ($coupon->isAlreadyUsedByReferee($referee->id)) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'You have already used this coupon.',
                'data'    => null,
            ];
        }

        $sportsFee      = $camp->sportsType->sports_fee ?? 0;
        $totalPrice     = $camp->price;
        $discountAmount = 0;

        if ($coupon->type === 'fixed') {
            $discountAmount = min($coupon->discount_value, $totalPrice);
        } else {
            $discountAmount = ($totalPrice * $coupon->discount_value) / 100;
        }

        $priceAfterDiscount = max(0, $totalPrice - $discountAmount);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Coupon is valid.',
            'data'    => [
                'coupon'  => [
                    'code'           => $coupon->code,
                    'type'           => $coupon->type,
                    'discount_value' => (float) $coupon->discount_value,
                ],
                'pricing' => [
                    'director_price'       => (float) ($totalPrice - $sportsFee),
                    'sports_fee'           => (float) $sportsFee,
                    'total_price'          => (float) $totalPrice,
                    'discount_amount'      => (float) $discountAmount,
                    'price_after_discount' => (float) $priceAfterDiscount,
                ],
            ],
        ];
    }

    /**
     * Register for camp V2 (Payment = Registration) with Coupon + Sports Fee support.
     *
     * @param  mixed       $referee
     * @param  int         $campId
     * @param  string|null $couponCode
     * @return array
     */
    public function registerForCamp($referee, int $campId, ?string $couponCode = null): array
    {
        if (!$referee) {
            return [
                'success' => false,
                'code'    => 401,
                'message' => 'User not authenticated.',
                'data'    => null,
            ];
        }

        $camp = Camp::with('sportsType')->where('id', $campId)
            ->where('status', 'active')
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found or inactive.',
                'data'    => null,
            ];
        }

        $existingRegistration = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $referee->id)
            ->first();

        if ($existingRegistration) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Already registered for this camp.',
                'data'    => [
                    'registration_status' => $existingRegistration->registration_status,
                    'registered_at'       => $existingRegistration->registered_at,
                    'checked_in_at'       => $existingRegistration->checked_in_at,
                    'can_check_in'        => $existingRegistration->canCheckIn($camp),
                ],
            ];
        }

        $hasValidPayment = $this->stripeService->hasValidPayment($campId, $referee->id);

        if ($hasValidPayment) {
            $payment = CampPayment::where('camp_id', $campId)
                ->where('referee_id', $referee->id)
                ->where('status', 'succeeded')
                ->first();

            $registration = CampRefereeCheckin::create([
                'camp_id'             => $campId,
                'referee_id'          => $referee->id,
                'payment_id'          => $payment->id,
                'registration_status' => 'registered',
                'registered_at'       => $payment->paid_at,
                'checked_in_at'       => null,
            ]);

            return [
                'success' => true,
                'code'    => 201,
                'message' => 'Registration completed successfully.',
                'data'    => [
                    'registration' => [
                        'id'            => $registration->id,
                        'status'        => $registration->registration_status,
                        'registered_at' => $registration->registered_at,
                        'can_check_in'  => $registration->canCheckIn($camp),
                    ],
                    'camp'         => [
                        'id'         => $camp->id,
                        'name'       => $camp->camp_name,
                        'location'   => $camp->location,
                        'start_date' => $camp->start_date->toDateString(),
                        'end_date'   => $camp->end_date->toDateString(),
                    ],
                    'payment'      => [
                        'amount'  => $payment->amount,
                        'paid_at' => $payment->paid_at->format('Y-m-d H:i:s'),
                    ],
                ],
            ];
        }

        $coupon = null;
        if (!empty($couponCode)) {
            $coupon = Coupon::where('code', $couponCode)->first();

            if (!$coupon) {
                return [
                    'success' => false,
                    'code'    => 400,
                    'message' => 'Invalid coupon code.',
                    'data'    => null,
                ];
            }

            if (!$coupon->isValidForCamp($campId)) {
                return [
                    'success' => false,
                    'code'    => 400,
                    'message' => 'Coupon is not valid for this camp or has expired.',
                    'data'    => null,
                ];
            }

            if (!$coupon->isValidForReferee($referee->id)) {
                return [
                    'success' => false,
                    'code'    => 400,
                    'message' => 'This coupon is not valid for you.',
                    'data'    => null,
                ];
            }

            if ($coupon->isAlreadyUsedByReferee($referee->id)) {
                return [
                    'success' => false,
                    'code'    => 400,
                    'message' => 'You have already used this coupon.',
                    'data'    => null,
                ];
            }
        }

        $paymentResult = $this->stripeService->createCheckoutSessionV2($camp, $referee, $coupon);

        if (!$paymentResult['success']) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => $paymentResult['error'],
                'data'    => [
                    'message' => $paymentResult['message'] ?? $paymentResult['error'],
                    'details' => $paymentResult['data'] ?? null,
                ],
            ];
        }

        // Free registration (amount < $0.50 threshold bypassed Stripe)
        if (!empty($paymentResult['free_registration'])) {
            $payment = $paymentResult['payment'];

            $registration = CampRefereeCheckin::create([
                'camp_id'             => $campId,
                'referee_id'          => $referee->id,
                'payment_id'          => $payment->id,
                'registration_status' => 'registered',
                'registered_at'       => $payment->paid_at,
                'checked_in_at'       => null,
            ]);

            return [
                'success' => true,
                'code'    => 201,
                'message' => 'Registration completed successfully.',
                'data'    => [
                    'payment_required' => false,
                    'registration'     => [
                        'id'            => $registration->id,
                        'status'        => $registration->registration_status,
                        'registered_at' => $registration->registered_at->format('Y-m-d H:i:s'),
                        'can_check_in'  => $registration->canCheckIn($camp),
                    ],
                    'camp'             => [
                        'id'         => $camp->id,
                        'name'       => $camp->camp_name,
                        'location'   => $camp->location,
                        'start_date' => $camp->start_date->toDateString(),
                        'end_date'   => $camp->end_date->toDateString(),
                    ],
                    'payment'          => [
                        'amount'          => $payment->amount,
                        'paid_at'         => $payment->paid_at->format('Y-m-d H:i:s'),
                        'discount_amount' => $paymentResult['discount_amount'] ?? 0,
                    ],
                ],
            ];
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Redirecting to payment...',
            'data'    => [
                'payment_required' => true,
                'checkout_url'     => $paymentResult['checkout_url'],
                'session_id'       => $paymentResult['session_id'],
                'expires_at'       => $paymentResult['expires_at'],
                'discount'         => [
                    'coupon_code'     => $coupon?->code,
                    'discount_amount' => $paymentResult['discount_amount'] ?? 0,
                ],
                'price_breakdown'  => $paymentResult['amount_breakdown'],
                'camp'             => [
                    'id'         => $camp->id,
                    'name'       => $camp->camp_name,
                    'price'      => $camp->price,
                    'location'   => $camp->location,
                    'start_date' => $camp->start_date->toDateString(),
                    'end_date'   => $camp->end_date->toDateString(),
                ],
            ],
        ];
    }
}
