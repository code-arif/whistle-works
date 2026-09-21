<?php

namespace App\Services\Api\Payment;

use App\Mail\AdminPaymentNotificationMail;
use App\Mail\NewCampRegistrationNorificationForDirector;
use App\Mail\PaymentFailedMail;
use App\Mail\PaymentSessionExpiredMail;
use App\Mail\RegistrationConfirmationMail;
use App\Models\CampPayment;
use App\Models\CampPaymentAttempt;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookService
{
    /**
     * Process incoming Stripe webhook payload.
     *
     * @param  string       $payload
     * @param  string|null  $sigHeader
     * @param  string|null  $webhookSecret
     * @return array
     */
    public function handleWebhook(string $payload, ?string $sigHeader, ?string $webhookSecret): array
    {
        try {
            $event = Webhook::constructEvent($payload, (string) $sigHeader, (string) $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe Webhook: Invalid payload', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Invalid payload',
                'data'    => [],
            ];
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe Webhook: Invalid signature', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Invalid signature',
                'data'    => [],
            ];
        }

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
                break;
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Webhook processed successfully',
            'data'    => ['status' => 'success'],
        ];
    }

    /**
     * Handle successful checkout session.
     *
     * @param  object  $session
     * @return void
     */
    protected function handleCheckoutSessionCompleted($session): void
    {
        $mailData = null;

        DB::beginTransaction();

        try {
            $attempt = CampPaymentAttempt::where('stripe_session_id', $session->id)->first();

            if (!$attempt) {
                Log::warning('Stripe Webhook: Payment attempt not found', ['session_id' => $session->id]);
                return;
            }

            if ($attempt->status === 'completed') {
                Log::info('Stripe Webhook: Payment already processed', ['session_id' => $session->id]);
                DB::commit();
                return;
            }

            if ($session->payment_status !== 'paid') {
                Log::warning('Stripe Webhook: Payment not paid', ['session_id' => $session->id]);
                DB::commit();
                return;
            }

            $user = User::find($attempt->referee_id);
            $camp = Camp::find($attempt->camp_id);

            if (!$user || !$camp) {
                Log::error('Stripe Webhook: User or camp not found', [
                    'user_id' => $attempt->referee_id,
                    'camp_id' => $attempt->camp_id,
                ]);
                DB::rollBack();
                return;
            }

            $existingPayment = CampPayment::where('stripe_session_id', $session->id)
                ->where('status', 'succeeded')
                ->first();

            if ($existingPayment) {
                $attempt->update(['status' => 'completed', 'completed_at' => now()]);
                DB::commit();
                return;
            }

            $payment = CampPayment::create([
                'camp_id'                  => $attempt->camp_id,
                'referee_id'               => $attempt->referee_id,
                'payment_attempt_id'       => $attempt->id,
                'stripe_payment_intent_id' => $session->payment_intent,
                'stripe_session_id'        => $session->id,
                'amount'                   => $attempt->amount,
                'currency'                 => strtolower($session->currency ?? 'usd'),
                'status'                   => 'succeeded',
                'paid_at'                  => now(),
                'metadata'                 => [
                    'payment_method' => $session->payment_method_types[0] ?? null,
                    'customer_email' => $session->customer_email
                        ?? $session->customer_details->email
                        ?? null,
                ],
            ]);

            $attempt->update(['status' => 'completed', 'completed_at' => now()]);

            $existingRegistration = CampRefereeCheckin::where('camp_id', $attempt->camp_id)
                ->where('referee_id', $attempt->referee_id)
                ->first();

            $registration = null;
            $isNewRegistration = false;

            if (!$existingRegistration) {
                $registration = CampRefereeCheckin::create([
                    'camp_id'             => $attempt->camp_id,
                    'referee_id'          => $attempt->referee_id,
                    'registration_status' => 'registered',
                    'registered_at'       => $payment->paid_at,
                    'checked_in_at'       => null,
                ]);
                $isNewRegistration = true;
            } else {
                $registration = $existingRegistration;
            }

            $admin = User::role('admin', 'web')->first();
            $director = User::find($camp->director_id);

            $mailData = [
                'user'              => $user,
                'camp'              => $camp,
                'payment'           => $payment,
                'registration'      => $registration,
                'isNewRegistration' => $isNewRegistration,
                'admin'             => $admin,
                'director'          => $director,
            ];

            Log::info('Stripe Webhook: DB operations completed, committing...', [
                'payment_id'      => $payment->id,
                'registration_id' => $registration->id,
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Stripe Webhook: DB transaction failed', [
                'session_id' => $session->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
            return;
        }

        if ($mailData) {
            $this->dispatchPostPaymentEmails($mailData);
        }
    }

    /**
     * Dispatch all notification emails separately after successful transaction commit.
     *
     * @param  array  $data
     * @return void
     */
    private function dispatchPostPaymentEmails(array $data): void
    {
        [
            'user'              => $user,
            'camp'              => $camp,
            'payment'           => $payment,
            'registration'      => $registration,
            'isNewRegistration' => $isNewRegistration,
            'admin'             => $admin,
            'director'          => $director,
        ] = $data;

        // 1: Registration confirmation — delay 0s
        if ($isNewRegistration && $registration) {
            try {
                Mail::to($user->email)
                    ->queue(new RegistrationConfirmationMail($user, $camp, $registration, $payment));
            } catch (Exception $e) {
                Log::error('Mail failed: RegistrationConfirmationMail', ['error' => $e->getMessage()]);
            }
        }

        // 2: Director notification — delay 10s
        if ($director) {
            try {
                Mail::to($director->email)
                    ->later(now()->addSeconds(10), new NewCampRegistrationNorificationForDirector($user, $camp, $payment));
            } catch (Exception $e) {
                Log::error('Mail failed: DirectorNotificationMail', ['error' => $e->getMessage()]);
            }
        }

        // 3: Admin notification — delay 15s
        if ($admin) {
            try {
                Mail::to('drewbontrager@gmail.com')
                    ->later(now()->addSeconds(15), new AdminPaymentNotificationMail($user, $camp, $payment, $admin));
            } catch (Exception $e) {
                Log::error('Mail failed: AdminPaymentNotificationMail', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Handle expired checkout session.
     *
     * @param  object  $session
     * @return void
     */
    protected function handleCheckoutSessionExpired($session): void
    {
        try {
            $attempt = CampPaymentAttempt::where('stripe_session_id', $session->id)
                ->where('status', 'pending')
                ->first();

            if ($attempt) {
                $attempt->update(['status' => 'failed']);

                $user = User::find($attempt->referee_id);
                $camp = Camp::find($attempt->camp_id);

                if ($user && $camp) {
                    try {
                        Mail::to($user->email)->queue(new PaymentSessionExpiredMail(
                            $user,
                            $camp,
                            $session->id,
                            'Your Payment Session Expired - Whistle Works'
                        ));
                    } catch (Exception $e) {
                        Log::error('Stripe Webhook: Failed to send session expired email', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                Log::info('Stripe Webhook: Checkout session expired', [
                    'session_id' => $session->id,
                    'camp_id'    => $attempt->camp_id,
                    'referee_id' => $attempt->referee_id,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Stripe Webhook: Failed to handle expired session', [
                'session_id' => $session->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle successful payment intent.
     *
     * @param  object  $paymentIntent
     * @return void
     */
    protected function handlePaymentIntentSucceeded($paymentIntent): void
    {
        Log::info('Stripe Webhook: Payment intent succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount'            => $paymentIntent->amount / 100,
            'currency'          => $paymentIntent->currency,
        ]);
    }

    /**
     * Handle failed payment intent.
     *
     * @param  object  $paymentIntent
     * @return void
     */
    protected function handlePaymentIntentFailed($paymentIntent): void
    {
        try {
            Log::warning('Stripe Webhook: Payment intent failed', [
                'payment_intent_id' => $paymentIntent->id,
                'amount'            => $paymentIntent->amount / 100,
                'error'             => $paymentIntent->last_payment_error->message ?? 'Unknown error',
                'error_code'        => $paymentIntent->last_payment_error->code ?? null,
            ]);

            if (isset($paymentIntent->metadata->session_id)) {
                $attempt = CampPaymentAttempt::where('stripe_session_id', $paymentIntent->metadata->session_id)
                    ->where('status', 'pending')
                    ->first();

                if ($attempt) {
                    $attempt->update(['status' => 'failed']);

                    $user = User::find($attempt->referee_id);
                    $camp = Camp::find($attempt->camp_id);

                    if ($user && $camp) {
                        try {
                            $errorMessage = $paymentIntent->last_payment_error->message ?? 'Payment was declined by your bank.';
                            Mail::to($user->email)->queue(new PaymentFailedMail(
                                $user,
                                $camp,
                                $errorMessage,
                                'Payment Failed - Whistle Works'
                            ));
                        } catch (Exception $e) {
                            Log::error('Stripe Webhook: Failed to send payment failed email', [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Stripe Webhook: Failed to handle payment failure', [
                'payment_intent_id' => $paymentIntent->id,
                'error'             => $e->getMessage(),
            ]);
        }
    }
}
