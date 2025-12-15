<?php

namespace Modules\Director\Http\Controllers\Api\Schedule;

use App\Models\CampPayment;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Director\Models\Camp;
use App\Http\Controllers\Controller;
use App\Services\StripePaymentService;
use Illuminate\Support\Facades\Validator;
use Modules\Director\Models\CampRefereeCheckin;

class RefereeCheckinController extends Controller
{
    use ApiResponse;

    protected $stripeService;

    public function __construct(StripePaymentService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * ONE-CLICK CHECK-IN
     * Handles both payment and check-in in single request
     */
    public function checkin(Request $request, $campId)
    {
        $referee = auth('api')->user();

        // Verify camp exists and is active
        $camp = Camp::where('id', $campId)
            ->where('status', 'active')
            ->first();

        if (!$camp) {
            return $this->error('Camp not found or inactive.', null, 404);
        }

        // Check if already checked in
        $existingCheckin = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $referee->id)
            ->first();

        if ($existingCheckin) {
            return $this->error('Already checked in to this camp.', null, 400);
        }

        // Check if payment already completed
        $hasValidPayment = $this->stripeService->hasValidPayment($campId, $referee->id);

        if ($hasValidPayment) {
            // Payment already done, just create check-in
            $payment = CampPayment::where('camp_id', $campId)
                ->where('referee_id', $referee->id)
                ->where('status', 'succeeded')
                ->first();

            $checkin = CampRefereeCheckin::create([
                'camp_id' => $campId,
                'referee_id' => $referee->id,
                'payment_id' => $payment->id,
                'checked_in_at' => now()
            ]);

            return $this->success(
                'Checked in successfully.',
                [
                    'checkin' => $checkin,
                    'camp' => [
                        'id' => $camp->id,
                        'name' => $camp->camp_name,
                        'location' => $camp->location,
                        'start_date' => $camp->start_date,
                        'end_date' => $camp->end_date
                    ]
                ],
                201
            );
        }

        // Payment not done yet - need to initiate payment
        $paymentResult = $this->stripeService->createCheckoutSession($camp, $referee);

        if (!$paymentResult['success']) {
            return $this->error(
                $paymentResult['error'],
                [
                    'message' => $paymentResult['message'] ?? $paymentResult['error'],
                    'details' => $paymentResult['data'] ?? null
                ],
                400
            );
        }

        // Return payment URL - frontend will redirect
        return $this->success(
            'Payment required. Redirecting to checkout...',
            [
                'payment_required' => true,
                'checkout_url' => $paymentResult['checkout_url'],
                'session_id' => $paymentResult['session_id'],
                'expires_at' => $paymentResult['expires_at'],
                'camp' => [
                    'id' => $camp->id,
                    'name' => $camp->camp_name,
                    'price' => $camp->price,
                    'location' => $camp->location
                ]
            ],
            200
        );
    }

    /**
     * Complete check-in after payment
     * This is called automatically after payment success
     */
    public function completeCheckin(Request $request)
    {
        $referee = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'camp_id' => 'required|exists:camps,id'
        ]);

        if ($validator->fails()) {
            return $this->error('Invalid request.', $validator->errors(), 422);
        }

        $campId = $request->camp_id;

        // Check if already checked in
        $existingCheckin = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $referee->id)
            ->first();

        if ($existingCheckin) {
            return $this->success(
                'Already checked in to this camp.',
                ['checkin' => $existingCheckin],
                200
            );
        }

        // Process payment
        $paymentResult = $this->stripeService->handleSuccess($request->session_id);

        if (!$paymentResult['success']) {
            return $this->error(
                'Payment verification failed.',
                ['message' => $paymentResult['error']],
                400
            );
        }

        // Create check-in
        $checkin = CampRefereeCheckin::create([
            'camp_id' => $campId,
            'referee_id' => $referee->id,
            'payment_id' => $paymentResult['payment']->id,
            'checked_in_at' => now()
        ]);

        $camp = Camp::find($campId);

        return $this->success(
            'Payment successful! You are now checked in.',
            [
                'checkin' => $checkin,
                'camp' => [
                    'id' => $camp->id,
                    'name' => $camp->camp_name,
                    'location' => $camp->location,
                    'start_date' => $camp->start_date,
                    'end_date' => $camp->end_date
                ],
                'payment' => [
                    'amount' => $paymentResult['payment']->amount,
                    'paid_at' => $paymentResult['payment']->paid_at
                ]
            ],
            201
        );
    }

    /**
     * Get my checked-in camps (Referee)
     */
    public function getMyCheckins()
    {
        $referee = auth('api')->user();

        $checkins = CampRefereeCheckin::where('referee_id', $referee->id)
            ->with([
                'camp:id,camp_name,location,start_date,end_date,camp_logo,price',
                'payment:id,amount,paid_at'
            ])
            ->latest('checked_in_at')
            ->get();

        $formatted = $checkins->map(function ($checkin) {
            return [
                'checkin_id' => $checkin->id,
                'camp' => [
                    'id' => $checkin->camp->id,
                    'camp_name' => $checkin->camp->camp_name,
                    'location' => $checkin->camp->location,
                    'camp_logo' => $checkin->camp->camp_logo ? asset($checkin->camp->camp_logo) : asset('default/no_image.webp'),
                    'start_date' => $checkin->camp->start_date,
                    'end_date' => $checkin->camp->end_date,
                    'price' => $checkin->camp->price
                ],
                'payment' => $checkin->payment ? [
                    'amount' => $checkin->payment->amount,
                    'paid_at' => $checkin->payment->paid_at->format('Y-m-d H:i:s')
                ] : null,
                'checked_in_at' => $checkin->checked_in_at->format('Y-m-d H:i:s')
            ];
        });

        return $this->success(
            'My check-ins fetched successfully.',
            ['checkin_camps' => $formatted],
            200
        );
    }

    /**
     * Get previous/past camps (ended camps only)
     */
    public function getPreviousCamps(Request $request)
    {
        $referee = auth('api')->user();
        $today = now()->toDateString();

        $checkins = CampRefereeCheckin::where('referee_id', $referee->id)
            ->with([
                'camp:id,camp_name,location,start_date,end_date,camp_logo,price,sports_type_name',
                'camp.sportsType:id,sports_name,icon',
                'payment:id,amount,paid_at'
            ])
            ->whereHas('camp', function ($query) use ($today) {
                $query->where('end_date', '<', $today);
            })
            ->latest('checked_in_at')
            ->paginate($request->get('per_page', 15));

        $formatted = $checkins->map(function ($checkin) {
            return [
                'checkin_id' => $checkin->id,
                'camp' => [
                    'id' => $checkin->camp->id,
                    'camp_name' => $checkin->camp->camp_name,
                    'location' => $checkin->camp->location,
                    'camp_logo' => $checkin->camp->camp_logo ? asset($checkin->camp->camp_logo) : asset('default/no_image.webp'),
                    'start_date' => $checkin->camp->start_date,
                    'end_date' => $checkin->camp->end_date,
                    'price' => $checkin->camp->price,
                    'sports_type' => $checkin->camp->sportsType ? [
                        'id' => $checkin->camp->sportsType->id,
                        'name' => $checkin->camp->sportsType->sports_name,
                        'icon' => $checkin->camp->sportsType->icon ? asset($checkin->camp->sportsType->icon) : asset('default/no_image.webp')
                    ] : null,
                    'status' => 'completed', // Past camp
                ],
                'payment' => $checkin->payment ? [
                    'amount' => $checkin->payment->amount,
                    'paid_at' => $checkin->payment->paid_at->format('Y-m-d H:i:s')
                ] : null,
                'checked_in_at' => $checkin->checked_in_at->format('Y-m-d H:i:s')
            ];
        });

        return $this->success(
            'Previous camps fetched successfully.',
            [
                'previous_camps' => $formatted,
                'pagination' => [
                    'total' => $checkins->total(),
                    'per_page' => $checkins->perPage(),
                    'current_page' => $checkins->currentPage(),
                    'last_page' => $checkins->lastPage(),
                ],
            ],
            200
        );
    }

    /**
     * Get ongoing/upcoming camps (active camps)
     */
    public function getActiveCamps(Request $request)
    {
        $referee = auth('api')->user();
        $today = now()->toDateString();

        $checkins = CampRefereeCheckin::where('referee_id', $referee->id)
            ->with([
                'camp:id,camp_name,location,start_date,end_date,camp_logo,price,sports_type_name',
                'camp.sportsType:id,sports_name,icon',
                'payment:id,amount,paid_at'
            ])
            ->whereHas('camp', function ($query) use ($today) {
                $query->where('end_date', '>=', $today);
            })
            ->latest('checked_in_at')
            ->paginate($request->get('per_page', 15));

        $formatted = $checkins->map(function ($checkin) use ($today) {
            $camp = $checkin->camp;

            // Determine status
            if ($camp->start_date > $today) {
                $status = 'upcoming';
            } elseif ($camp->start_date <= $today && $camp->end_date >= $today) {
                $status = 'ongoing';
            } else {
                $status = 'completed';
            }

            return [
                'checkin_id' => $checkin->id,
                'camp' => [
                    'id' => $camp->id,
                    'camp_name' => $camp->camp_name,
                    'location' => $camp->location,
                    'camp_logo' => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                    'start_date' => $camp->start_date,
                    'end_date' => $camp->end_date,
                    'price' => $camp->price,
                    'sports_type' => $camp->sportsType ? [
                        'id' => $camp->sportsType->id,
                        'name' => $camp->sportsType->sports_name,
                        'icon' => $camp->sportsType->icon ? asset($camp->sportsType->icon) : asset('default/no_image.webp')
                    ] : null,
                    'status' => $status,
                ],
                'payment' => $checkin->payment ? [
                    'amount' => $checkin->payment->amount,
                    'paid_at' => $checkin->payment->paid_at->format('Y-m-d H:i:s')
                ] : null,
                'checked_in_at' => $checkin->checked_in_at->format('Y-m-d H:i:s')
            ];
        });

        return $this->success(
            'Active camps fetched successfully.',
            [
                'active_camps' => $formatted,
                'pagination' => [
                    'total' => $checkins->total(),
                    'per_page' => $checkins->perPage(),
                    'current_page' => $checkins->currentPage(),
                    'last_page' => $checkins->lastPage(),
                ],
            ],
            200
        );
    }
}
