<?php

namespace App\Http\Controllers\Api;

use Exception;
use Stripe\Webhook;
use App\Models\User;
use App\Models\CampPayment;
use Illuminate\Http\Request;
use App\Mail\PaymentFailedMail;
use Modules\Director\Models\Camp;
use App\Models\CampPaymentAttempt;
use Illuminate\Support\Facades\DB;
use App\Mail\PaymentSuccessfulMail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentSessionExpiredMail;
use App\Mail\AdminPaymentNotificationMail;
use App\Mail\NewCampRegistrationNorificationForDirector;
use App\Mail\RegistrationConfirmationMail;
use Modules\Director\Models\CampRefereeCheckin;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    /**
     * Handle Stripe webhook events
     */
    public function HandlePaymentWebhook(Request $request)
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
    // protected function handleCheckoutSessionCompleted($session)
    // {
    //     DB::beginTransaction();

    //     try {
    //         // Find payment attempt
    //         $attempt = CampPaymentAttempt::where('stripe_session_id', $session->id)->first();

    //         if (!$attempt) {
    //             Log::warning('Stripe Webhook: Payment attempt not found', [
    //                 'session_id' => $session->id
    //             ]);
    //             return;
    //         }

    //         // Check if already processed
    //         if ($attempt->status === 'completed') {
    //             Log::info('Stripe Webhook: Payment already processed', [
    //                 'session_id' => $session->id,
    //                 'attempt_id' => $attempt->id
    //             ]);
    //             DB::commit();
    //             return;
    //         }

    //         // Verify payment status
    //         if ($session->payment_status !== 'paid') {
    //             Log::warning('Stripe Webhook: Payment not completed', [
    //                 'session_id' => $session->id,
    //                 'payment_status' => $session->payment_status
    //             ]);
    //             DB::commit();
    //             return;
    //         }

    //         // Get user and camp
    //         $user = User::find($attempt->referee_id);
    //         $camp = Camp::find($attempt->camp_id);

    //         if (!$user || !$camp) {
    //             Log::error('Stripe Webhook: User or camp not found', [
    //                 'user_id' => $attempt->referee_id,
    //                 'camp_id' => $attempt->camp_id
    //             ]);
    //             DB::rollBack();
    //             return;
    //         }

    //         // Check if payment record already exists
    //         $existingPayment = CampPayment::where('stripe_session_id', $session->id)
    //             ->where('status', 'succeeded')
    //             ->first();

    //         if ($existingPayment) {
    //             Log::info('Stripe Webhook: Payment record already exists', [
    //                 'payment_id' => $existingPayment->id
    //             ]);

    //             // Update attempt status
    //             $attempt->update([
    //                 'status' => 'completed',
    //                 'completed_at' => now()
    //             ]);

    //             DB::commit();
    //             return;
    //         }

    //         // Create payment record
    //         $payment = CampPayment::create([
    //             'camp_id' => $attempt->camp_id,
    //             'referee_id' => $attempt->referee_id,
    //             'payment_attempt_id' => $attempt->id,
    //             'stripe_payment_intent_id' => $session->payment_intent,
    //             'stripe_session_id' => $session->id,
    //             'amount' => $attempt->amount,
    //             'currency' => strtolower($session->currency ?? 'usd'),
    //             'status' => 'succeeded',
    //             'paid_at' => now(),
    //             'metadata' => [
    //                 'payment_method' => $session->payment_method_types[0] ?? null,
    //                 'customer_email' => $session->customer_email ?? $session->customer_details->email ?? null
    //             ]
    //         ]);

    //         // Update attempt status
    //         $attempt->update([
    //             'status' => 'completed',
    //             'completed_at' => now()
    //         ]);

    //         // Check if registration already exists
    //         $existingRegistration = CampRefereeCheckin::where('camp_id', $attempt->camp_id)
    //             ->where('referee_id', $attempt->referee_id)
    //             ->first();

    //         if (!$existingRegistration) {
    //             // Create automatic registration after successful payment
    //             $registration = CampRefereeCheckin::create([
    //                 'camp_id' => $attempt->camp_id,
    //                 'referee_id' => $attempt->referee_id,
    //                 'registration_status' => 'registered',
    //                 'registered_at' => $payment->paid_at,
    //                 'checked_in_at' => null,
    //             ]);

    //             Log::info('Stripe Webhook: Payment and registration completed', [
    //                 'payment_id' => $payment->id,
    //                 'registration_id' => $registration->id,
    //                 'camp_id' => $attempt->camp_id,
    //                 'referee_id' => $attempt->referee_id,
    //                 'amount' => $payment->amount
    //             ]);

    //             // Send registration confirmation email to referee
    //             try {
    //                 Mail::to($user->email)->queue(new RegistrationConfirmationMail($user, $camp, $registration));
    //             } catch (Exception $e) {
    //                 Log::error('Stripe Webhook: Failed to send registration email', [
    //                     'error' => $e->getMessage(),
    //                     'user_id' => $user->id,
    //                     'camp_id' => $camp->id
    //                 ]);
    //             }
    //         } else {
    //             Log::info('Stripe Webhook: Payment completed, registration already exists', [
    //                 'payment_id' => $payment->id,
    //                 'registration_id' => $existingRegistration->id,
    //                 'camp_id' => $attempt->camp_id,
    //                 'referee_id' => $attempt->referee_id
    //             ]);
    //         }

    //         // Send payment success email to referee
    //         try {
    //             Mail::to($user->email)->queue(new PaymentSuccessfulMail($user, $camp, $payment));
    //         } catch (Exception $e) {
    //             Log::error('Stripe Webhook: Failed to send payment success email', [
    //                 'error' => $e->getMessage(),
    //                 'user_id' => $user->id,
    //                 'camp_id' => $camp->id
    //             ]);
    //         }

    //         // Send admin notification email
    //         try {
    //             $admin = User::role('admin')->first();
    //             $director = User::find($camp->director_id);

    //             // Director mail (queued)
    //             if ($director) {
    //                 Mail::to($director->email)
    //                     ->queue(new NewCampRegistrationNorificationForDirector(
    //                         $user,
    //                         $camp,
    //                         $payment
    //                     ));
    //             }

    //             // Admin mails (queued)
    //             Mail::to($admin->email)
    //                 ->queue(new AdminPaymentNotificationMail(
    //                     $user,
    //                     $camp,
    //                     $payment,
    //                     $admin
    //                 ));
    //         } catch (Exception $e) {
    //             Log::error('Stripe Webhook: Failed to send notification emails', [
    //                 'error' => $e->getMessage()
    //             ]);
    //         }


    //         DB::commit();
    //     } catch (Exception $e) {
    //         DB::rollBack();

    //         Log::error('Stripe Webhook: Failed to process checkout session', [
    //             'session_id' => $session->id,
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //     }
    // }

    protected function handleCheckoutSessionCompleted($session)
    {
        // We will collect the necessary data here to send mail.
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
                    'camp_id' => $attempt->camp_id
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

            // Payment record making
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
                        ?? null
                ]
            ]);

            $attempt->update(['status' => 'completed', 'completed_at' => now()]);

            //  Registration check ও create
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

            // Collect data for mail — I won't mail right now.
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

            DB::commit(); // now commit

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Stripe Webhook: DB transaction failed', [
                'session_id' => $session->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString()
            ]);
            return; // I will not send mail.
        }

        // Send mail after transaction commit
        // Now all records are in DB, queue worker can read safely
        if ($mailData) {
            $this->dispatchPostPaymentEmails($mailData);
        }
    }

    /**
     * Dispatch all mails separately after commit
     * Each mail in a separate try-catch — if one fails, the rest will go through
     */
    private function dispatchPostPaymentEmails(array $data): void
    {
        [
            'user' => $user,
            'camp' => $camp,
            'payment' => $payment,
            'registration' => $registration,
            'isNewRegistration' => $isNewRegistration,
            'admin' => $admin,
            'director' => $director
        ] = $data;

        // 1️: Registration confirmation — delay 0s
        if ($isNewRegistration && $registration) {
            try {
                Mail::to($user->email)
                    ->queue(new RegistrationConfirmationMail($user, $camp, $registration));
            } catch (Exception $e) {
                Log::error('Mail failed: RegistrationConfirmationMail', ['error' => $e->getMessage()]);
            }
        }

        // 2️: Payment success — delay 5s
        try {
            Mail::to($user->email)
                ->later(now()->addSeconds(5), new PaymentSuccessfulMail($user, $camp, $payment));
        } catch (Exception $e) {
            Log::error('Mail failed: PaymentSuccessfulMail', ['error' => $e->getMessage()]);
        }

        // 3️: Director notification — delay 10s
        if ($director) {
            try {
                Mail::to($director->email)
                    ->later(now()->addSeconds(10), new NewCampRegistrationNorificationForDirector($user, $camp, $payment));
            } catch (Exception $e) {
                Log::error('Mail failed: DirectorNotificationMail', ['error' => $e->getMessage()]);
            }
        }

        // 4️: Admin notification — delay 15s
        if ($admin) {
            try {
                Mail::to($admin->email)
                    ->later(now()->addSeconds(15), new AdminPaymentNotificationMail($user, $camp, $payment, $admin));
            } catch (Exception $e) {
                Log::error('Mail failed: AdminPaymentNotificationMail', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Handle expired checkout session
     */
    protected function handleCheckoutSessionExpired($session)
    {
        try {
            $attempt = CampPaymentAttempt::where('stripe_session_id', $session->id)
                ->where('status', 'pending')
                ->first();

            if ($attempt) {
                $attempt->update(['status' => 'failed']);

                // Get user and camp
                $user = User::find($attempt->referee_id);
                $camp = Camp::find($attempt->camp_id);

                if ($user && $camp) {
                    // Send session expired email
                    try {
                        Mail::to($user->email)->queue(new PaymentSessionExpiredMail(
                            $user,
                            $camp,
                            $session->id,
                            'Your Payment Session Expired - Whistle Works'
                        ));
                    } catch (Exception $e) {
                        Log::error('Stripe Webhook: Failed to send session expired email', [
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                Log::info('Stripe Webhook: Checkout session expired', [
                    'session_id' => $session->id,
                    'camp_id' => $attempt->camp_id,
                    'referee_id' => $attempt->referee_id
                ]);
            }
        } catch (Exception $e) {
            Log::error('Stripe Webhook: Failed to handle expired session', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
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
            'amount' => $paymentIntent->amount / 100,
            'currency' => $paymentIntent->currency
        ]);

        // This event fires before checkout.session.completed
        // We can use it for logging or additional processing
    }

    /**
     * Handle failed payment intent
     */
    protected function handlePaymentIntentFailed($paymentIntent)
    {
        try {
            Log::warning('Stripe Webhook: Payment intent failed', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount / 100,
                'error' => $paymentIntent->last_payment_error->message ?? 'Unknown error',
                'error_code' => $paymentIntent->last_payment_error->code ?? null
            ]);

            // Try to find the related payment attempt via metadata
            if (isset($paymentIntent->metadata->session_id)) {
                $attempt = CampPaymentAttempt::where('stripe_session_id', $paymentIntent->metadata->session_id)
                    ->where('status', 'pending')
                    ->first();

                if ($attempt) {
                    $attempt->update(['status' => 'failed']);

                    // Get user and camp
                    $user = User::find($attempt->referee_id);
                    $camp = Camp::find($attempt->camp_id);

                    if ($user && $camp) {
                        // Send payment failed email
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
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Stripe Webhook: Failed to handle payment failure', [
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
