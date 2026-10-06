<?php

namespace Modules\Director\Services\Payment;

use App\Services\StripePaymentService;
use Exception;
use Illuminate\Support\Facades\Log;

class StripeConnectService
{
    protected StripePaymentService $stripeService;

    public function __construct(StripePaymentService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Start Stripe Connect onboarding for director.
     *
     * @param  mixed        $user
     * @param  string|null  $returnUrl
     * @param  string|null  $refreshUrl
     * @return array
     */
    public function connect($user, ?string $returnUrl = null, ?string $refreshUrl = null): array
    {
        try {
            if (!$user) {
                return [
                    'success' => false,
                    'code'    => 404,
                    'message' => 'User not authenticated.',
                    'data'    => [],
                ];
            }

            if (!$user->hasRole('director')) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'Only directors can connect Stripe accounts',
                    'data'    => null,
                ];
            }

            $stripeAccountId = $user->stripe_account_id;

            // Create new account if none exists
            if (!$stripeAccountId) {
                $stripeAccountId = $this->stripeService->createConnectAccount($user);
            }

            // If already fully onboarded → return dashboard link
            if ($this->stripeService->isConnectAccountReady($stripeAccountId)) {
                $dashboardUrl = $this->stripeService->getConnectDashboardLink($stripeAccountId);

                return [
                    'success' => true,
                    'code'    => 200,
                    'message' => 'Stripe account already connected',
                    'data'    => [
                        'onboarding_url' => null,
                        'dashboard_url'  => $dashboardUrl,
                        'stripe_account' => $stripeAccountId,
                        'is_connected'   => true,
                    ],
                ];
            }

            // Generate / refresh onboarding link
            $onboardingUrl = $this->stripeService->createConnectOnboardingLink($stripeAccountId, $returnUrl, $refreshUrl);

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Stripe onboarding link generated. Complete the onboarding to receive payouts.',
                'data'    => [
                    'onboarding_url' => $onboardingUrl,
                    'dashboard_url'  => null,
                    'stripe_account' => $stripeAccountId,
                    'is_connected'   => false,
                ],
            ];
        } catch (Exception $e) {
            Log::error('Stripe connect error: ' . $e->getMessage(), [
                'user_id' => $user?->id ?? auth('api')->id(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to initiate Stripe onboarding: ' . $e->getMessage(),
                'data'    => ['exception' => $e->getMessage()],
            ];
        }
    }

    /**
     * Check if director's Stripe account is fully onboarded.
     *
     * @param  mixed $user
     * @return array
     */
    public function status($user): array
    {
        try {
            if (!$user) {
                return [
                    'success' => false,
                    'code'    => 401,
                    'message' => 'User not authenticated.',
                    'data'    => [],
                ];
            }

            $stripeAccountId = $user->stripe_account_id;

            if (!$stripeAccountId) {
                return [
                    'success' => true,
                    'code'    => 200,
                    'message' => 'Stripe account not connected',
                    'data'    => [
                        'is_connected'        => false,
                        'can_receive_payouts' => false,
                        'stripe_account'      => null,
                    ],
                ];
            }

            $isReady = $this->stripeService->isConnectAccountReady($stripeAccountId);

            // Update profile if newly onboarded
            if ($isReady && !$user->stripe_onboarded_at) {
                $user->update(['stripe_onboarded_at' => now()]);
            }

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Stripe account status',
                'data'    => [
                    'is_connected'        => true,
                    'can_receive_payouts' => $isReady,
                    'stripe_account'      => $stripeAccountId,
                    'onboarded_at'        => $user->stripe_onboarded_at,
                ],
            ];
        } catch (Exception $e) {
            Log::error('Stripe status error: ' . $e->getMessage());

            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to check Stripe status',
                'data'    => null,
            ];
        }
    }

    /**
     * Get Stripe Express Dashboard link.
     *
     * @param  mixed $user
     * @return array
     */
    public function dashboard($user): array
    {
        try {
            if (!$user) {
                return [
                    'success' => false,
                    'code'    => 401,
                    'message' => 'User not authenticated.',
                    'data'    => [],
                ];
            }

            $stripeAccountId = $user->stripe_account_id;

            if (!$stripeAccountId) {
                return [
                    'success' => false,
                    'code'    => 404,
                    'message' => 'No Stripe account connected. Please complete onboarding first.',
                    'data'    => null,
                ];
            }

            if (!$this->stripeService->isConnectAccountReady($stripeAccountId)) {
                // Return a fresh onboarding link instead
                $onboardingUrl = $this->stripeService->createConnectOnboardingLink($stripeAccountId);

                return [
                    'success' => false,
                    'code'    => 400,
                    'message' => 'Stripe onboarding not complete. Please finish the onboarding process.',
                    'data'    => ['onboarding_url' => $onboardingUrl],
                ];
            }

            $dashboardUrl = $this->stripeService->getConnectDashboardLink($stripeAccountId);

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Dashboard link generated',
                'data'    => ['url' => $dashboardUrl],
            ];
        } catch (Exception $e) {
            Log::error('Stripe dashboard error: ' . $e->getMessage());

            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to generate dashboard link: ' . $e->getMessage(),
                'data'    => null,
            ];
        }
    }
}
