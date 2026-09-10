<?php

namespace Modules\Director\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Services\StripePaymentService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeConnectController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected StripePaymentService $stripeService,
    ) {}

    /**
     * Start Stripe Connect onboarding for director
     * POST /api/v1/director/stripe/connect
     */
    public function connect(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->error([], 'User not authenticated.', 404);
            }

            if (!$user->hasRole('director')) {
                return $this->error(null, 'Only directors can connect Stripe accounts', 403);
            }

            $stripeAccountId = $user->stripe_account_id;

            // Create new account if none exists
            if (!$stripeAccountId) {
                $stripeAccountId = $this->stripeService->createConnectAccount($user);
            }

            // If already fully onboarded → return dashboard link
            if ($this->stripeService->isConnectAccountReady($stripeAccountId)) {
                $dashboardUrl = $this->stripeService->getConnectDashboardLink($stripeAccountId);

                return $this->success('Stripe account already connected', [
                    'onboarding_url' => null,
                    'dashboard_url'  => $dashboardUrl,
                    'stripe_account' => $stripeAccountId,
                    'is_connected'   => true,
                ]);
            }

            // Generate / refresh onboarding link
            $returnUrl = $request->input('return_url');
            $refreshUrl = $request->input('refresh_url');
            $onboardingUrl = $this->stripeService->createConnectOnboardingLink($stripeAccountId, $returnUrl, $refreshUrl);

            return $this->success('Stripe onboarding link generated. Complete the onboarding to receive payouts.', [
                'onboarding_url' => $onboardingUrl,
                'dashboard_url'  => null,
                'stripe_account' => $stripeAccountId,
                'is_connected'   => false,
            ]);
        } catch (Exception $e) {
            Log::error('Stripe connect error: ' . $e->getMessage(), [
                'user_id' => auth('api')->id(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return $this->error(
                ['exception' => $e->getMessage()],
                'Failed to initiate Stripe onboarding: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Check if director's Stripe account is fully onboarded
     * GET /api/v1/director/stripe/status
     */
    public function status(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->error([], 'User not authenticated.', 401);
            }

            $stripeAccountId = $user->stripe_account_id;

            if (!$stripeAccountId) {
                return $this->success('Stripe account not connected', [
                    'is_connected' => false,
                    'can_receive_payouts' => false,
                    'stripe_account' => null,
                ]);
            }

            $isReady = $this->stripeService->isConnectAccountReady($stripeAccountId);

            // Update profile if newly onboarded
            if ($isReady && !$user->stripe_onboarded_at) {
                $user->update(['stripe_onboarded_at' => now()]);
            }

            return $this->success('Stripe account status', [
                'is_connected'        => true,
                'can_receive_payouts' => $isReady,
                'stripe_account'      => $stripeAccountId,
                'onboarded_at'        => $user->stripe_onboarded_at,
            ]);
        } catch (Exception $e) {
            Log::error('Stripe status error: ' . $e->getMessage());
            return $this->error(null, 'Failed to check Stripe status', 500);
        }
    }

    /**
     * Get Stripe Express Dashboard link
     * GET /api/v1/director/stripe/dashboard
     */
    public function dashboard(Request $request)
    {
        try {
            $user = auth('api')->user();
            $stripeAccountId = $user->stripe_account_id;

            if (!$stripeAccountId) {
                return $this->error(null, 'No Stripe account connected. Please complete onboarding first.', 404);
            }

            if (!$this->stripeService->isConnectAccountReady($stripeAccountId)) {
                // Return a fresh onboarding link instead
                $onboardingUrl = $this->stripeService->createConnectOnboardingLink($stripeAccountId);
                return $this->error(
                    ['onboarding_url' => $onboardingUrl],
                    'Stripe onboarding not complete. Please finish the onboarding process.',
                    400
                );
            }

            $dashboardUrl = $this->stripeService->getConnectDashboardLink($stripeAccountId);

            return $this->success('Dashboard link generated', ['url' => $dashboardUrl]);
        } catch (Exception $e) {
            Log::error('Stripe dashboard error: ' . $e->getMessage());
            return $this->error(null, 'Failed to generate dashboard link: ' . $e->getMessage(), 500);
        }
    }
}
