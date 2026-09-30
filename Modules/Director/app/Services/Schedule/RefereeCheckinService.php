<?php

namespace Modules\Director\Services\Schedule;

use App\Mail\CampCheckinNotificationMail;
use App\Models\CampPayment;
use App\Models\User;
use App\Services\StripePaymentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;

class RefereeCheckinService
{
    protected StripePaymentService $stripeService;

    public function __construct(StripePaymentService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Register for camp (Payment = Registration) (V1).
     *
     * @param  mixed   $referee
     * @param  int     $campId
     * @param  Request $request
     * @return array
     */
    public function registerForCamp($referee, int $campId, Request $request): array
    {
        if (!$referee) {
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

        // Check if already registered
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

        // Check if payment already completed (edge case: payment done but registration missing)
        $hasValidPayment = $this->stripeService->hasValidPayment($campId, $referee->id);

        if ($hasValidPayment) {
            $payment = CampPayment::where('camp_id', $campId)
                ->where('referee_id', $referee->id)
                ->where('status', 'succeeded')
                ->first();

            $registration = CampRefereeCheckin::create([
                'camp_id'             => $campId,
                'referee_id'          => $referee->id,
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

        // Payment not done yet - initiate payment
        $paymentResult = $this->stripeService->createCheckoutSession($camp, $referee);

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

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Redirecting to payment...',
            'data'    => [
                'payment_required' => true,
                'checkout_url'     => $paymentResult['checkout_url'],
                'session_id'       => $paymentResult['session_id'],
                'expires_at'       => $paymentResult['expires_at'],
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

    /**
     * Handle payment success callback (auto-registration).
     *
     * @param  mixed  $referee
     * @param  string $sessionId
     * @param  int    $campId
     * @return array
     */
    public function handlePaymentSuccess($referee, string $sessionId, int $campId): array
    {
        if (!$referee) {
            return [
                'success' => false,
                'code'    => 401,
                'message' => 'User not authenticated.',
                'data'    => null,
            ];
        }

        // Check if already registered
        $existingRegistration = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $referee->id)
            ->first();

        if ($existingRegistration) {
            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Already registered for this camp.',
                'data'    => [
                    'registration' => [
                        'id'            => $existingRegistration->id,
                        'status'        => $existingRegistration->registration_status,
                        'registered_at' => $existingRegistration->registered_at->format('Y-m-d H:i:s'),
                        'checked_in_at' => $existingRegistration->checked_in_at?->format('Y-m-d H:i:s'),
                    ],
                ],
            ];
        }

        // Process payment
        $paymentResult = $this->stripeService->handleSuccess($sessionId);

        if (!$paymentResult['success']) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Payment verification failed.',
                'data'    => ['message' => $paymentResult['error']],
            ];
        }

        // Auto registration on payment success
        $registration = CampRefereeCheckin::create([
            'camp_id'             => $campId,
            'referee_id'          => $referee->id,
            'registration_status' => 'registered',
            'registered_at'       => $paymentResult['payment']->paid_at,
            'checked_in_at'       => null,
        ]);

        $camp = Camp::find($campId);

        return [
            'success' => true,
            'code'    => 201,
            'message' => 'Payment successful! You are now registered. Check-in when the camp starts.',
            'data'    => [
                'registration' => [
                    'id'            => $registration->id,
                    'status'        => $registration->registration_status,
                    'registered_at' => $registration->registered_at->format('Y-m-d H:i:s'),
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
                    'amount'  => $paymentResult['payment']->amount,
                    'paid_at' => $paymentResult['payment']->paid_at->format('Y-m-d H:i:s'),
                ],
                'next_step'    => "You can check-in starting from {$camp->start_date->toDateString()}.",
            ],
        ];
    }

    /**
     * Check-in to camp (Physical attendance).
     *
     * @param  mixed $referee
     * @param  int   $campId
     * @return array
     */
    public function checkIn($referee, int $campId): array
    {
        if (!$referee) {
            return [
                'success' => false,
                'code'    => 401,
                'message' => 'User not authenticated.',
                'data'    => null,
            ];
        }

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

        $registration = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $referee->id)
            ->first();

        if (!$registration) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'You are not registered for this camp. Please register first.',
                'data'    => [
                    'requires_registration' => true,
                    'camp'                  => [
                        'id'    => $camp->id,
                        'name'  => $camp->camp_name,
                        'price' => $camp->price,
                    ],
                ],
            ];
        }

        if ($registration->isCheckedIn()) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'You have already checked in to this camp.',
                'data'    => [
                    'checked_in_at' => $registration->checked_in_at->format('Y-m-d H:i:s'),
                ],
            ];
        }

        $now              = now();
        $checkinStartTime = $camp->start_date->subHours(24);

        if ($now->lt($checkinStartTime)) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Check-in will be available 24 hours before the camp starts.',
                'data'    => [
                    'checkin_available_from' => $checkinStartTime->format('Y-m-d H:i:s'),
                    'camp_starts_on'         => $camp->start_date->format('Y-m-d H:i:s'),
                ],
            ];
        }

        if ($now->gt($camp->end_date)) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'This camp has already ended. Check-in is no longer available.',
                'data'    => [
                    'camp_ended_on' => $camp->end_date->format('Y-m-d H:i:s'),
                ],
            ];
        }

        $registration->update([
            'registration_status' => 'checked_in',
            'checked_in_at'       => now(),
        ]);

        $this->sendCheckinNotificationToDirectors($camp, $referee, $registration);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Successfully checked in! Welcome to the camp.',
            'data'    => [
                'registration' => [
                    'id'            => $registration->id,
                    'status'        => $registration->registration_status,
                    'registered_at' => $registration->registered_at->format('Y-m-d H:i:s'),
                    'checked_in_at' => $registration->checked_in_at->format('Y-m-d H:i:s'),
                ],
                'camp'         => [
                    'id'         => $camp->id,
                    'name'       => $camp->camp_name,
                    'location'   => $camp->location,
                    'start_date' => $camp->start_date->toDateString(),
                    'end_date'   => $camp->end_date->toDateString(),
                ],
            ],
        ];
    }

    /**
     * Get my registrations (all camps I've registered for).
     *
     * @param  mixed   $referee
     * @param  Request $request
     * @return array
     */
    public function getMyRegistrations($referee, Request $request): array
    {
        $today   = now()->toDateString();
        $perPage = (int) $request->get('per_page', 10);
        $total   = CampRefereeCheckin::where('referee_id', $referee->id)->count();

        $registrations = CampRefereeCheckin::where('referee_id', $referee->id)
            ->where('registration_status', 'registered')
            ->whereHas('camp', function ($query) use ($today) {
                $query->where('end_date', '>=', $today);
            })
            ->with([
                'camp:id,camp_name,location,start_date,end_date,camp_logo,price,sports_type_name',
                'camp.sportsType:id,sports_name,icon',
            ])
            ->with('camp.director')
            ->latest('registered_at')
            ->paginate($perPage);

        $campIds  = $registrations->pluck('camp_id')->toArray();
        $payments = CampPayment::where('referee_id', $referee->id)
            ->whereIn('camp_id', $campIds)
            ->get()
            ->keyBy('camp_id');

        $formatted = $registrations->getCollection()->map(function ($registration) use ($payments, $today) {
            $camp    = $registration->camp;
            $payment = $payments->get($camp->id);

            if ($camp->end_date < $today) {
                $campStatus = 'completed';
            } elseif ($camp->start_date > $today) {
                $campStatus = 'upcoming';
            } else {
                $campStatus = 'ongoing';
            }

            return [
                'registration_id'     => $registration->id,
                'registration_status' => $registration->registration_status,
                'can_check_in'        => $registration->canCheckIn($camp),
                'registered_at'       => $registration->registered_at->format('Y-m-d H:i:s'),
                'checked_in_at'       => $registration->checked_in_at?->format('Y-m-d H:i:s'),
                'camp'                => [
                    'id'          => $camp->id,
                    'camp_name'   => $camp->camp_name,
                    'location'    => $camp->location,
                    'logo'        => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                    'start_date'  => $camp->start_date->toDateString(),
                    'end_date'    => $camp->end_date->toDateString(),
                    'price'       => $camp->price,
                    'status'      => $campStatus,
                    'sports_type' => $camp->sportsType ? [
                        'id'   => $camp->sportsType->id,
                        'name' => $camp->sportsType->sports_name,
                        'icon' => $camp->sportsType->icon ? asset($camp->sportsType->icon) : asset('default/no_image.webp'),
                    ] : null,
                    'director'    => [
                        'id'   => $camp->director->id,
                        'name' => $camp->director->first_name . ' ' . $camp->director->last_name ?? null,
                    ],
                ],
                'payment'             => $payment ? [
                    'amount'  => $payment->amount,
                    'paid_at' => $payment->paid_at->format('Y-m-d H:i:s'),
                ] : null,
            ];
        });

        $totalRegistered = CampRefereeCheckin::where('referee_id', $referee->id)
            ->where('registration_status', 'registered')
            ->whereHas('camp', function ($query) use ($today) {
                $query->where('end_date', '>=', $today);
            })
            ->count();

        $totalCheckedIn = CampRefereeCheckin::where('referee_id', $referee->id)
            ->where('registration_status', 'checked_in')
            ->whereHas('camp', function ($query) use ($today) {
                $query->where('end_date', '>=', $today);
            })
            ->count();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'My registered camp fetched successfully.',
            'data'    => [
                'registrations' => $formatted,
                'pagination'    => [
                    'total'        => $registrations->total(),
                    'per_page'     => $registrations->perPage(),
                    'current_page' => $registrations->currentPage(),
                    'last_page'    => $registrations->lastPage(),
                ],
                'summary'       => [
                    'total'           => $total,
                    'registered_only' => $totalRegistered,
                    'checked_in'      => $totalCheckedIn,
                ],
            ],
        ];
    }

    /**
     * Get my checked-in camps (Referee).
     *
     * @param  mixed   $referee
     * @param  Request $request
     * @return array
     */
    public function getMyCheckins($referee, Request $request): array
    {
        $today   = now()->toDateString();
        $perPage = (int) $request->get('per_page', 10);
        $total   = CampRefereeCheckin::where('referee_id', $referee->id)->count();

        $checkins = CampRefereeCheckin::where('referee_id', $referee->id)
            ->where('registration_status', 'checked_in')
            ->whereHas('camp', function ($query) use ($today) {
                $query->where('end_date', '>=', $today);
            })
            ->whereNotNull('checked_in_at')
            ->with([
                'camp:id,camp_name,location,start_date,end_date,camp_logo,price,sports_type_name',
                'camp.sportsType:id,sports_name,icon',
            ])
            ->with('camp.director')
            ->latest('checked_in_at')
            ->paginate($perPage);

        $campIds  = $checkins->pluck('camp_id')->toArray();
        $payments = CampPayment::where('referee_id', $referee->id)
            ->whereIn('camp_id', $campIds)
            ->get()
            ->keyBy('camp_id');

        $formatted = $checkins->map(function ($checkin) use ($payments, $today) {
            $camp    = $checkin->camp;
            $payment = $payments->get($camp->id);

            if ($camp->end_date < $today) {
                $campStatus = 'completed';
            } elseif ($camp->start_date > $today) {
                $campStatus = 'upcoming';
            } else {
                $campStatus = 'ongoing';
            }

            return [
                'registration_id'     => $checkin->id,
                'registration_status' => $checkin->registration_status,
                'can_check_in'        => $checkin->canCheckIn($camp),
                'registered_at'       => $checkin->registered_at->format('Y-m-d H:i:s'),
                'checked_in_at'       => $checkin->checked_in_at?->format('Y-m-d H:i:s'),
                'camp'                => [
                    'id'          => $camp->id,
                    'camp_name'   => $camp->camp_name,
                    'location'    => $camp->location,
                    'logo'        => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                    'start_date'  => $camp->start_date->toDateString(),
                    'end_date'    => $camp->end_date->toDateString(),
                    'price'       => $camp->price,
                    'status'      => $campStatus,
                    'sports_type' => $camp->sportsType ? [
                        'id'   => $camp->sportsType->id,
                        'name' => $camp->sportsType->sports_name,
                        'icon' => $camp->sportsType->icon ? asset($camp->sportsType->icon) : asset('default/no_image.webp'),
                    ] : null,
                    'director'    => [
                        'id'   => $camp->director->id,
                        'name' => $camp->director->first_name . ' ' . $camp->director->last_name ?? null,
                    ],
                ],
                'payment'             => $payment ? [
                    'amount'  => $payment->amount,
                    'paid_at' => $payment->paid_at->format('Y-m-d H:i:s'),
                ] : null,
            ];
        });

        $totalRegistered = CampRefereeCheckin::where('referee_id', $referee->id)
            ->where('registration_status', 'registered')
            ->whereHas('camp', function ($query) use ($today) {
                $query->where('end_date', '>=', $today);
            })
            ->count();

        $totalCheckedIn = CampRefereeCheckin::where('referee_id', $referee->id)
            ->where('registration_status', 'checked_in')
            ->whereHas('camp', function ($query) use ($today) {
                $query->where('end_date', '>=', $today);
            })
            ->count();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'My checked-in camp fetched successfully.',
            'data'    => [
                'checked_in' => $formatted,
                'pagination' => [
                    'total'        => $checkins->total(),
                    'per_page'     => $checkins->perPage(),
                    'current_page' => $checkins->currentPage(),
                    'last_page'    => $checkins->lastPage(),
                ],
                'summary'    => [
                    'total'           => $total,
                    'registered_only' => $totalRegistered,
                    'checked_in'      => $totalCheckedIn,
                ],
            ],
        ];
    }

    /**
     * Get active camps (upcoming + ongoing).
     *
     * @param  mixed   $referee
     * @param  Request $request
     * @return array
     */
    public function getActiveCamps($referee, Request $request): array
    {
        $today   = now()->toDateString();
        $perPage = (int) $request->get('per_page', 10);

        $registrations = CampRefereeCheckin::where('referee_id', $referee->id)
            ->with([
                'camp:id,camp_name,location,start_date,end_date,camp_logo,price,sports_type_name',
                'camp.sportsType:id,sports_name,icon',
            ])
            ->whereHas('camp', function ($query) use ($today) {
                $query->where('end_date', '>=', $today);
            })
            ->latest('registered_at')
            ->paginate($perPage);

        $campIds  = $registrations->pluck('camp_id')->toArray();
        $payments = CampPayment::where('referee_id', $referee->id)
            ->whereIn('camp_id', $campIds)
            ->get()
            ->keyBy('camp_id');

        $registrations->getCollection()->map(function ($registration) use ($payments, $today) {
            $camp       = $registration->camp;
            $payment    = $payments->get($camp->id);
            $campStatus = $camp->start_date > $today ? 'upcoming' : 'ongoing';

            return [
                'registration_id'     => $registration->id,
                'registration_status' => $registration->registration_status,
                'can_check_in'        => $registration->canCheckIn($camp),
                'registered_at'       => $registration->registered_at?->format('Y-m-d H:i:s'),
                'checked_in_at'       => $registration->checked_in_at?->format('Y-m-d H:i:s'),
                'camp'                => [
                    'id'          => $camp->id,
                    'name'        => $camp->camp_name,
                    'location'    => $camp->location,
                    'logo'        => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                    'start_date'  => $camp->start_date->toDateString(),
                    'end_date'    => $camp->end_date->toDateString(),
                    'status'      => $campStatus,
                    'sports_type' => $camp->sportsType ? [
                        'name' => $camp->sportsType->sports_name,
                        'icon' => $camp->sportsType->icon ? asset($camp->sportsType->icon) : null,
                    ] : null,
                ],
                'payment'             => $payment ? [
                    'amount'  => $payment->amount,
                    'paid_at' => $payment->paid_at->format('Y-m-d H:i:s'),
                ] : null,
            ];
        });

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Active camps fetched successfully.',
            'data'    => [
                'active_camps' => $registrations->items(),
                'pagination'   => [
                    'total'        => $registrations->total(),
                    'per_page'     => $registrations->perPage(),
                    'current_page' => $registrations->currentPage(),
                    'last_page'    => $registrations->lastPage(),
                ],
            ],
        ];
    }

    /**
     * Get previous/completed camps.
     *
     * @param  mixed   $referee
     * @param  Request $request
     * @return array
     */
    public function getPreviousCamps($referee, Request $request): array
    {
        $today   = now()->toDateString();
        $perPage = (int) $request->get('per_page', 10);

        $registrations = CampRefereeCheckin::where('referee_id', $referee->id)
            ->with([
                'camp:id,camp_name,location,start_date,end_date,camp_logo,price',
                'camp.sportsType:id,sports_name,icon',
            ])
            ->with('camp.director')
            ->whereHas('camp', function ($query) use ($today) {
                $query->where('end_date', '<', $today);
            })
            ->latest('registered_at')
            ->paginate($perPage);

        $campIds  = $registrations->pluck('camp_id')->toArray();
        $payments = CampPayment::where('referee_id', $referee->id)
            ->whereIn('camp_id', $campIds)
            ->get()
            ->keyBy('camp_id');

        $formatted = $registrations->getCollection()->map(function ($registration) use ($payments) {
            $camp    = $registration->camp;
            $payment = $payments->get($camp->id);

            return [
                'registration_id'     => $registration->id,
                'registration_status' => $registration->registration_status,
                'registered_at'       => $registration->registered_at->format('Y-m-d H:i:s'),
                'checked_in_at'       => $registration->checked_in_at?->format('Y-m-d H:i:s'),
                'camp'                => [
                    'id'          => $camp->id,
                    'name'        => $camp->camp_name,
                    'location'    => $camp->location,
                    'address'     => $camp->address ?? null,
                    'sports_type' => $camp->sportsType->sports_name ?? null,
                    'logo'        => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                    'start_date'  => $camp->start_date->toDateString(),
                    'end_date'    => $camp->end_date->toDateString(),
                    'status'      => 'completed',
                    'director'    => [
                        'id'   => $camp->director->id ?? null,
                        'name' => trim(($camp->director->first_name ?? '') . ' ' . ($camp->director->last_name ?? '')),
                    ],
                ],
                'payment'             => $payment ? [
                    'amount'  => $payment->amount,
                    'paid_at' => $payment->paid_at->format('Y-m-d H:i:s'),
                ] : null,
            ];
        });

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Previous camps fetched successfully.',
            'data'    => [
                'previous_camps' => $formatted->values(),
                'pagination'     => [
                    'total'        => $registrations->total(),
                    'per_page'     => $registrations->perPage(),
                    'current_page' => $registrations->currentPage(),
                    'last_page'    => $registrations->lastPage(),
                ],
            ],
        ];
    }

    /**
     * Get previous/completed camps for director only.
     *
     * @param  mixed   $director
     * @param  Request $request
     * @return array
     */
    public function getDirectorPreviousCamps($director, Request $request): array
    {
        $today   = now()->toDateString();
        $perPage = (int) $request->get('per_page', 10);

        $camps = Camp::where('director_id', $director->id)
            ->with(['sportsType', 'checkedInReferees', 'schedule'])
            ->orderBy('created_at', 'desc')
            ->where('end_date', '<', $today)
            ->paginate($perPage);

        $formatted = $camps->getCollection()->map(function ($camp) {
            return [
                'id'               => $camp->id,
                'name'             => $camp->camp_name,
                'logo'             => $camp->camp_logo ? asset($camp->camp_logo) : asset('default/no_image.webp'),
                'location'         => $camp->location,
                'address'          => $camp->address ?? null,
                'timezone'         => $camp->timezone ?? null,
                'sports_type'      => $camp->sportsType ?? null,
                'status'           => $camp->status,
                'total_referees'   => $camp->checkedInReferees->count(),
                'total_courts'     => $camp->schedule ? $camp->schedule->gameSlots()->count() : 0,
                'start_date'       => $camp->start_date->toDateString(),
                'end_date'         => $camp->end_date->toDateString(),
                'completed_status' => 'completed',
            ];
        });

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Previous camps fetched successfully.',
            'data'    => [
                'previous_camps' => $formatted->values(),
                'pagination'     => [
                    'total'        => $camps->total(),
                    'per_page'     => $camps->perPage(),
                    'current_page' => $camps->currentPage(),
                    'last_page'    => $camps->lastPage(),
                ],
            ],
        ];
    }

    /**
     * Send check-in notification to camp directors.
     */
    private function sendCheckinNotificationToDirectors(Camp $camp, User $referee, CampRefereeCheckin $registration): void
    {
        try {
            $directors = [];

            if ($camp->director_id) {
                $director = User::find($camp->director_id);
                if ($director) {
                    $directors[] = $director;
                }
            }

            if (!$directors && $camp->created_by) {
                $director = User::find($camp->created_by);
                if ($director) {
                    $directors[] = $director;
                }
            }

            if (empty($directors)) {
                $directors = User::role('admin')->take(3)->get();
            }

            foreach ($directors as $director) {
                try {
                    Mail::to($director->email)->queue(
                        new CampCheckinNotificationMail($director, $referee, $camp, $registration)
                    );

                    Log::info('Check-in notification sent to director', [
                        'director_id'     => $director->id,
                        'director_email'  => $director->email,
                        'referee_id'      => $referee->id,
                        'camp_id'         => $camp->id,
                        'registration_id' => $registration->id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send check-in notification to director', [
                        'director_id' => $director->id,
                        'error'       => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in sendCheckinNotificationToDirectors', [
                'error'      => $e->getMessage(),
                'camp_id'    => $camp->id,
                'referee_id' => $referee->id,
            ]);
        }
    }
}
