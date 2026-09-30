<?php

namespace Modules\Director\Services\Payment;

use App\Models\CampPayment;
use App\Services\StripePaymentService;
use Modules\Director\Models\Camp;

class CampPaymentService
{
    protected StripePaymentService $stripeService;

    public function __construct(StripePaymentService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Initiate payment for camp check-in.
     *
     * @param  mixed $user
     * @param  mixed $campId
     * @return array
     */
    public function initiatePayment($user, $campId): array
    {
        if (!$user) {
            return [
                'success' => false,
                'code'    => 401,
                'message' => 'User not authenticated.',
                'data'    => null,
            ];
        }

        // Verify camp exists and is active
        $camp = Camp::where('id', $campId)
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

        // Check if can initiate payment
        $result = $this->stripeService->createCheckoutSession($camp, $user);

        if (!$result['success']) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => $result['error'],
                'data'    => isset($result['data']) ? $result['data'] : ['message' => $result['message'] ?? null],
            ];
        }

        return [
            'success' => true,
            'code'    => 201,
            'message' => 'Payment session created successfully.',
            'data'    => [
                'session_id'   => $result['session_id'],
                'checkout_url' => $result['checkout_url'],
                'expires_at'   => $result['expires_at'],
                'camp'         => [
                    'id'    => $camp->id,
                    'name'  => $camp->camp_name,
                    'price' => $camp->price,
                ],
            ],
        ];
    }

    /**
     * Handle successful payment callback.
     *
     * @param  string $sessionId
     * @return array
     */
    public function handleSuccess(string $sessionId): array
    {
        $result = $this->stripeService->handleSuccess($sessionId);

        if (!$result['success']) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => $result['error'],
                'data'    => ['message' => $result['message'] ?? null],
            ];
        }

        $message = isset($result['already_processed'])
            ? 'Payment already processed.'
            : 'Payment completed successfully.';

        return [
            'success' => true,
            'code'    => 200,
            'message' => $message,
            'data'    => [
                'payment_id' => $result['payment']->id,
                'camp_id'    => $result['camp_id'],
                'amount'     => $result['payment']->amount,
                'paid_at'    => $result['payment']->paid_at,
                'next_step'  => 'You can now check in to the camp.',
            ],
        ];
    }

    /**
     * Handle cancelled payment callback.
     *
     * @param  string $sessionId
     * @return array
     */
    public function handleCancel(string $sessionId): array
    {
        $result = $this->stripeService->handleCancel($sessionId);

        if (!$result['success']) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => $result['error'],
                'data'    => null,
            ];
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Payment cancelled.',
            'data'    => [
                'message'      => 'You cancelled the payment. You can retry after ' . config('payment.retry_cooldown', 5) . ' minutes.',
                'can_retry_at' => $result['can_retry_at'],
                'camp_id'      => $result['attempt']->camp_id,
            ],
        ];
    }

    /**
     * Get payment status for a camp.
     *
     * @param  mixed $user
     * @param  mixed $campId
     * @return array
     */
    public function getPaymentStatus($user, $campId): array
    {
        if (!$user) {
            return [
                'success' => false,
                'code'    => 401,
                'message' => 'User not authenticated.',
                'data'    => null,
            ];
        }

        $camp = Camp::find($campId);
        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        $canPay = $this->stripeService->canInitiatePayment($camp, $user);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Payment status retrieved.',
            'data'    => [
                'camp_id'              => $campId,
                'camp_name'            => $camp->camp_name,
                'price'                => $camp->price,
                'can_initiate_payment' => $canPay['can_pay'],
                'reason'               => $canPay['reason'] ?? null,
                'details'              => $canPay,
            ],
        ];
    }

    /**
     * Get payment history for referee.
     *
     * @param  mixed $user
     * @return array
     */
    public function getPaymentHistory($user): array
    {
        if (!$user) {
            return [
                'success' => false,
                'code'    => 401,
                'message' => 'User not authenticated.',
                'data'    => null,
            ];
        }

        $payments = CampPayment::where('referee_id', $user->id)
            ->with('camp:id,camp_name,location,camp_logo,price')
            ->latest('paid_at')
            ->get();

        $formatted = $payments->map(function ($payment) {
            return [
                'payment_id' => $payment->id,
                'amount'     => $payment->amount,
                'currency'   => strtoupper($payment->currency),
                'status'     => $payment->status,
                'paid_at'    => $payment->paid_at->format('Y-m-d H:i:s'),
                'camp'       => [
                    'id'       => $payment->camp?->id,
                    'name'     => $payment->camp?->camp_name,
                    'location' => $payment->camp?->location,
                    'logo'     => $payment->camp?->camp_logo ? asset($payment->camp->camp_logo) : asset('default/no_image.webp'),
                    'price'    => $payment->camp?->price,
                ],
            ];
        });

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Payment history retrieved.',
            'data'    => ['payments' => $formatted],
        ];
    }
}
