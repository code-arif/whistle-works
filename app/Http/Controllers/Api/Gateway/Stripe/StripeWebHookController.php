<?php

namespace App\Http\Controllers\Api\Gateway\Stripe;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CampPaymentAttempt;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    /**
     * Handle Stripe webhook events
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe Webhook: Invalid payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe Webhook: Invalid signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle different event types
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->data->object);
                break;

            case 'checkout.session.expired':
                $this->handleCheckoutSessionExpired($event->data->object);
                break;

            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;

            default:
                Log::info('Stripe Webhook: Unhandled event type', ['type' => $event->type]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle successful checkout session
     */
    protected function handleCheckoutSessionCompleted($session)
    {
        $attempt = CampPaymentAttempt::where('stripe_session_id', $session->id)->first();

        if (!$attempt) {
            Log::warning('Stripe Webhook: Payment attempt not found', ['session_id' => $session->id]);
            return;
        }

        if ($attempt->status === 'completed') {
            Log::info('Stripe Webhook: Payment already processed', ['session_id' => $session->id]);
            return;
        }

        // Update will be handled by the success callback, but we can log here
        Log::info('Stripe Webhook: Checkout session completed', [
            'session_id' => $session->id,
            'camp_id' => $attempt->camp_id,
            'referee_id' => $attempt->referee_id
        ]);
    }

    /**
     * Handle expired checkout session
     */
    protected function handleCheckoutSessionExpired($session)
    {
        $attempt = CampPaymentAttempt::where('stripe_session_id', $session->id)
            ->where('status', 'pending')
            ->first();

        if ($attempt) {
            $attempt->update(['status' => 'failed']);

            Log::info('Stripe Webhook: Checkout session expired', [
                'session_id' => $session->id,
                'camp_id' => $attempt->camp_id
            ]);
        }
    }

    /**
     * Handle successful payment intent
     */
    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        Log::info('Stripe Webhook: Payment intent succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount / 100
        ]);
    }

    /**
     * Handle failed payment intent
     */
    protected function handlePaymentIntentFailed($paymentIntent)
    {
        Log::warning('Stripe Webhook: Payment intent failed', [
            'payment_intent_id' => $paymentIntent->id,
            'error' => $paymentIntent->last_payment_error->message ?? 'Unknown error'
        ]);
    }
}
