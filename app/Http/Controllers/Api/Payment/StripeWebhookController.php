<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Services\Api\Payment\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    protected StripeWebhookService $webhookService;

    public function __construct(StripeWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle Stripe webhook events.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function HandlePaymentWebhook(Request $request): JsonResponse
    {
        $payload       = $request->getContent();
        $sigHeader     = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook');

        $result = $this->webhookService->handleWebhook($payload, $sigHeader, $webhookSecret);

        if (!$result['success']) {
            return response()->json(['error' => $result['message']], $result['code']);
        }

        return response()->json($result['data'], $result['code']);
    }
}
