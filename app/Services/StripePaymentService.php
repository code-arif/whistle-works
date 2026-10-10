<?php

namespace App\Services;

use App\Models\CampPayment;
use App\Models\CampPaymentAttempt;
use App\Models\Coupon;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Director\Models\Camp;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\StripeClient;

class StripePaymentService
{

    // Processing fee percentage (3%)
    const PROCESSING_FEE_PERCENTAGE = 3;

    protected $stripeClient;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $this->stripeClient = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Calculate total amount with processing fee
     */
    private function calculateTotalAmount(float $basePrice): array
    {
        $processingFee = round($basePrice * (self::PROCESSING_FEE_PERCENTAGE / 100), 2);
        $totalAmount = round($basePrice + $processingFee, 2);

        return [
            'base_price' => $basePrice,
            'processing_fee' => $processingFee,
            'processing_fee_percentage' => self::PROCESSING_FEE_PERCENTAGE,
            'total_amount' => $totalAmount
        ];
    }

    /**
     * Check if referee can initiate payment for camp
     */
    public function canInitiatePayment(Camp $camp, User $referee): array
    {
        // Check if already paid
        $existingPayment = CampPayment::where('camp_id', $camp->id)
            ->where('referee_id', $referee->id)
            ->where('status', 'succeeded')
            ->first();

        if ($existingPayment) {
            return [
                'can_pay' => false,
                'reason' => 'Already paid for this camp',
                'payment' => $existingPayment
            ];
        }

        // Check for pending attempts
        $recentAttempt = CampPaymentAttempt::where('camp_id', $camp->id)
            ->where('referee_id', $referee->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($recentAttempt) {
            return [
                'can_pay' => false,
                'reason' => 'Please wait for 1 minutes before creating a new payment',
                'session_id' => $recentAttempt->stripe_session_id,
                'expires_at' => $recentAttempt->expires_at
            ];
        }

        // Check for cancelled/failed attempts within cooldown period
        $cooldownMinutes = config('payment.retry_cooldown', 1);
        $recentFailedAttempt = CampPaymentAttempt::where('camp_id', $camp->id)
            ->where('referee_id', $referee->id)
            ->whereIn('status', ['cancelled', 'failed'])
            ->where('updated_at', '>', now()->subMinutes($cooldownMinutes))
            ->first();

        if ($recentFailedAttempt) {
            $waitUntil = $recentFailedAttempt->updated_at->addMinutes($cooldownMinutes);
            $waitMinutes = now()->diffInMinutes($waitUntil, false);

            return [
                'can_pay' => false,
                'reason' => 'Please wait before retrying payment',
                'wait_until' => $waitUntil,
                'wait_minutes' => max(1, ceil($waitMinutes))
            ];
        }

        return ['can_pay' => true];
    }

    /**
     * Get valid image URL for Stripe
     */
    private function getValidImageUrl(?string $logoPath): ?string
    {
        if (!$logoPath) {
            return null;
        }

        // Generate full URL
        $fullUrl = url($logoPath);

        // Check if URL is valid and uses HTTPS in production
        $isHttps = str_starts_with($fullUrl, 'https://');
        $isLocalhost = str_contains($fullUrl, 'localhost') || str_contains($fullUrl, '127.0.0.1');

        // In production, only return HTTPS URLs
        // In local development, skip image to avoid Stripe errors
        if (app()->environment('production')) {
            return $isHttps ? $fullUrl : null;
        }

        // In local/staging, don't send image URL as Stripe won't accept it
        return null;
    }

    /**
     * Create Stripe checkout session
     */
    public function createCheckoutSession(Camp $camp, User $referee): array
    {
        return $this->createCheckoutSessionV2($camp, $referee, null);
    }



    /**
     * Handle successful payment
     */
    public function handleSuccess(string $sessionId): array
    {
        DB::beginTransaction();

        try {
            // Retrieve the session from Stripe
            $session = Session::retrieve([
                'id' => $sessionId,
                'expand' => ['payment_intent']
            ]);

            if ($session->payment_status !== 'paid') {
                return [
                    'success' => false,
                    'error' => 'Payment not completed'
                ];
            }

            // Find payment attempt
            $attempt = CampPaymentAttempt::where('stripe_session_id', $sessionId)->first();

            if (!$attempt) {
                return [
                    'success' => false,
                    'error' => 'Payment attempt not found'
                ];
            }

            // Check if already processed
            if ($attempt->status === 'completed') {
                $payment = CampPayment::where('payment_attempt_id', $attempt->id)->first();
                return [
                    'success' => true,
                    'already_processed' => true,
                    'payment' => $payment
                ];
            }

            // Create payment record
            $payment = CampPayment::create([
                'camp_id' => $attempt->camp_id,
                'referee_id' => $attempt->referee_id,
                'payment_attempt_id' => $attempt->id,
                'stripe_payment_intent_id' => $session->payment_intent->id,
                'stripe_session_id' => $sessionId,
                'amount' => $attempt->amount,
                'currency' => strtolower($session->currency ?? 'usd'),
                'status' => 'succeeded',
                'paid_at' => now(),
                'coupon_id' => $attempt->coupon_id ?? null,
                'discount_amount' => $attempt->discount_amount ?? 0,
                'admin_fee' => $attempt->admin_fee ?? 0,
                'director_amount' => $attempt->director_amount ?? 0,
                'metadata' => [
                    'payment_method' => $session->payment_intent->payment_method ?? null,
                    'customer_email' => $session->customer_email,
                    'version' => $session->metadata->version ?? 'v1'
                ]
            ]);

            // Update attempt
            $attempt->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            // Record coupon usage (per-referee one-time tracking)
            if ($payment->coupon_id) {
                $coupon = \App\Models\Coupon::find($payment->coupon_id);
                if ($coupon) {
                    $coupon->recordUsage($payment->referee_id, $payment->camp_id, $payment->id);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'payment' => $payment,
                'camp_id' => $attempt->camp_id
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => 'Failed to process payment',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle cancelled payment
     */
    public function handleCancel(string $sessionId): array
    {
        $attempt = CampPaymentAttempt::where('stripe_session_id', $sessionId)->first();

        if (!$attempt) {
            return [
                'success' => false,
                'error' => 'Payment attempt not found'
            ];
        }

        if ($attempt->status === 'pending') {
            $attempt->update(['status' => 'cancelled']);
        }

        return [
            'success' => true,
            'attempt' => $attempt,
            'can_retry_at' => now()->addMinutes(config('payment.retry_cooldown', 1))
        ];
    }

    /**
     * Verify payment exists for camp and referee
     */
    public function hasValidPayment(int $campId, int $refereeId): bool
    {
        return CampPayment::where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->where('status', 'succeeded')
            ->exists();
    }

    // ─────────────────────────────────────────────────────────────────
    // V2: CHECKOUT SESSION (WITH SPLIT PAYMENT, SPORTS FEE & COUPONS)
    // ─────────────────────────────────────────────────────────────────

    public function createCheckoutSessionV2(Camp $camp, User $referee, ?Coupon $coupon = null): array
    {
        DB::beginTransaction();

        try {
            $canPay = $this->canInitiatePayment($camp, $referee);
            if (!$canPay['can_pay']) {
                return [
                    'success' => false,
                    'error' => $canPay['reason'],
                    'data' => $canPay
                ];
            }

            // Mark old expired attempts as failed
            CampPaymentAttempt::where('camp_id', $camp->id)
                ->where('referee_id', $referee->id)
                ->where('status', 'pending')
                ->where('expires_at', '<', now())
                ->update(['status' => 'failed']);

            // ── Price Breakdown ──
            // stored camp.price = director_price + sports_fee (admin fee)
            $sportsFee = $camp->sportsType->sports_fee ?? 0;
            $directorBasePrice = $camp->price - $sportsFee; // Director's share before discount
            $basePrice = $camp->price; // Total price before any discount
            $discountAmount = 0;

            if ($coupon) {
                if ($coupon->type === 'fixed') {
                    $discountAmount = min($coupon->discount_value, $basePrice);
                } else {
                    $discountAmount = ($basePrice * $coupon->discount_value) / 100;
                }
                $basePrice -= $discountAmount;
            }

            // Ensure base price doesn't go below 0
            $basePrice = max(0, $basePrice);

            // Calculate amounts after discount — proportionally reduce director & admin shares
            $discountRatio = $camp->price > 0 ? ($discountAmount / $camp->price) : 0;
            $appliedSportsFee = $sportsFee - ($sportsFee * $discountRatio);
            $appliedDirectorAmount = $basePrice - $appliedSportsFee;

            // Calculate total amount with processing fee (3% Stripe fee)
            $amountCalculation = $this->calculateTotalAmount($basePrice);

            $stripeFee = $amountCalculation['processing_fee']; // 3% Stripe processing
            $totalAmount = $amountCalculation['total_amount'];

            // If total amount is less than $0.50 (Stripe minimum transaction threshold),
            // bypass Stripe payment creation and mark payment as succeeded immediately.
            if ($totalAmount < 0.50) {
                $freeSessionId = 'FREE_SESSION_' . Str::upper(Str::random(12));

                $attempt = CampPaymentAttempt::create([
                    'camp_id' => $camp->id,
                    'referee_id' => $referee->id,
                    'stripe_session_id' => $freeSessionId,
                    'amount' => $totalAmount,
                    'status' => 'completed',
                    'completed_at' => now(),
                    'expires_at' => now(),
                    'coupon_id' => $coupon ? $coupon->id : null,
                    'discount_amount' => $discountAmount,
                    'admin_fee' => 0,
                    'director_amount' => 0,
                ]);

                $payment = CampPayment::create([
                    'camp_id' => $camp->id,
                    'referee_id' => $referee->id,
                    'payment_attempt_id' => $attempt->id,
                    'stripe_payment_intent_id' => 'FREE_' . Str::upper(Str::random(12)),
                    'stripe_session_id' => $freeSessionId,
                    'amount' => $totalAmount,
                    'currency' => 'usd',
                    'status' => 'succeeded',
                    'paid_at' => now(),
                    'coupon_id' => $coupon ? $coupon->id : null,
                    'discount_amount' => $discountAmount,
                    'admin_fee' => 0,
                    'director_amount' => 0,
                    'metadata' => [
                        'coupon_code' => $coupon?->code,
                        'free_registration' => true,
                        'note' => 'Bypassed Stripe payment because total amount was less than $0.50 threshold'
                    ]
                ]);

                if ($coupon) {
                    $coupon->recordUsage($referee->id, $camp->id, $payment->id);
                }

                DB::commit();

                return [
                    'success' => true,
                    'free_registration' => true,
                    'payment' => $payment,
                    'discount_amount' => $discountAmount,
                    'amount_breakdown' => $amountCalculation
                ];
            }

            // For Connect split: platform retains stripe_fee + sports_fee, director gets his share
            $adminFee = $stripeFee + $appliedSportsFee;
            $directorAmount = $appliedDirectorAmount;

            // Prepare description
            $description = "Location: {$camp->location}";
            if ($camp->start_date && $camp->end_date) {
                $description .= " | {$camp->start_date} to {$camp->end_date}";
            }
            if ($coupon) {
                $description .= " | Coupon Applied: {$coupon->code}";
            }
            $description .= " | Includes {$amountCalculation['processing_fee_percentage']}% processing fee";

            $imageUrl = $this->getValidImageUrl($camp->camp_logo);
            $productData = [
                'name' => "Camp Registration: {$camp->camp_name}",
                'description' => $description,
            ];
            if ($imageUrl) {
                $productData['images'] = [$imageUrl];
            }

            $sessionData = [
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => $productData,
                        'unit_amount' => (int)($totalAmount * 100), // Total with fee
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => config('payment.success_url') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('payment.cancel_url') . '?session_id={CHECKOUT_SESSION_ID}',
                'metadata' => [
                    'camp_id' => $camp->id,
                    'referee_id' => $referee->id,
                    'camp_name' => $camp->camp_name,
                    'referee_email' => $referee->email,
                    'base_price' => $amountCalculation['base_price'],
                    'sports_fee' => $appliedSportsFee,
                    'director_price' => $appliedDirectorAmount,
                    'processing_fee' => $amountCalculation['processing_fee'],
                    'processing_fee_percentage' => $amountCalculation['processing_fee_percentage'],
                    'total_amount' => $totalAmount,
                    'coupon_id' => $coupon ? $coupon->id : null,
                    'discount_amount' => $discountAmount,
                    'admin_fee' => $adminFee,
                    'director_amount' => $directorAmount,
                    'version' => 'v2-sports-fee'
                ],
                'customer_email' => $referee->email,
                'expires_at' => now()->addMinutes(30)->timestamp
            ];

            // If director has a connected account, split the payment
            $director = $camp->director;
            $directorStripeAccountId = $director ? $director->stripe_account_id : null;

            if ($directorStripeAccountId && $this->isConnectAccountReady($directorStripeAccountId) && $totalAmount > 0) {
                $sessionData['payment_intent_data'] = [
                    'application_fee_amount' => (int)($adminFee * 100),
                    'transfer_data' => [
                        'destination' => $directorStripeAccountId,
                    ],
                ];
            }

            $session = Session::create($sessionData);

            $attempt = CampPaymentAttempt::create([
                'camp_id' => $camp->id,
                'referee_id' => $referee->id,
                'stripe_session_id' => $session->id,
                'amount' => $totalAmount,
                'status' => 'pending',
                'expires_at' => now()->addMinutes((int) config('payment.retry_cooldown', 2)),
                'coupon_id' => $coupon ? $coupon->id : null,
                'discount_amount' => $discountAmount,
                'admin_fee' => $adminFee,
                'director_amount' => $directorAmount,
            ]);

            DB::commit();

            return [
                'success' => true,
                'session_id' => $session->id,
                'checkout_url' => $session->url,
                'attempt_id' => $attempt->id,
                'expires_at' => $attempt->expires_at,
                'amount_breakdown' => [
                    'camp_price' => (float) $camp->price,
                    'sports_fee' => (float) $sportsFee,
                    'director_price' => (float) $directorBasePrice,
                    'discount' => (float) $discountAmount,
                    'price_after_discount' => (float) $basePrice,
                    'processing_fee' => (float) $stripeFee,
                    'total' => (float) $totalAmount,
                ],
                'discount_amount' => (float) $discountAmount
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Stripe payment session V2 creation failed', [
                'camp_id' => $camp->id,
                'referee_id' => $referee->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create payment session',
                'message' => $e->getMessage()
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // STRIPE CONNECT — DIRECTOR ONBOARDING
    // ─────────────────────────────────────────────────────────────────

    public function createConnectAccount(User $director): string
    {
        $account = $this->stripeClient->accounts->create([
            'type'  => 'express',
            'email' => $director->email,
            'metadata' => ['user_id' => (string)$director->id],
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers'     => ['requested' => true],
            ],
            'settings' => [
                'payouts' => [
                    'schedule' => ['interval' => 'daily'],
                ],
            ],
        ]);

        $director->update(['stripe_account_id' => $account->id]);

        return $account->id;
    }

    public function createConnectOnboardingLink(string $stripeAccountId, ?string $returnUrl = null, ?string $refreshUrl = null): string
    {
        $frontendUrl = rtrim(env('TEST_FRONTEND', env('FRONTEND', config('app.test_frontend_url', config('app.frontend_url', 'https://test.whistleworks.org')))), '/');

        $defaultReturnUrl = $frontendUrl . '/director-dashboard/stripe-connection-success';
        $defaultRefreshUrl = $frontendUrl . '/director-dashboard/stripe-connect';

        $link = $this->stripeClient->accountLinks->create([
            'account'     => $stripeAccountId,
            'refresh_url' => $refreshUrl ?? $defaultRefreshUrl,
            'return_url'  => $returnUrl ?? $defaultReturnUrl,
            'type'        => 'account_onboarding',
            'collect'     => 'eventually_due',
        ]);

        return $link->url;
    }

    public function isConnectAccountReady(string $stripeAccountId): bool
    {
        try {
            $account = $this->stripeClient->accounts->retrieve($stripeAccountId);
            return $account->charges_enabled && $account->payouts_enabled;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getConnectDashboardLink(string $stripeAccountId): string
    {
        $link = $this->stripeClient->accounts->createLoginLink($stripeAccountId);
        return $link->url;
    }
}
