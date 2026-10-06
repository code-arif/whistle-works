<?php

namespace App\Services\Api\Evaluator;

use App\Http\Resources\Evaluator\CampEvaluatorRegistrationResource;
use App\Mail\Evaluator\Registration\CampRegistrationConfirmationForEvaluator;
use App\Mail\Evaluator\Registration\NewCampRegistrationNotificationForDirector;
use App\Models\CampEvaluatorRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Modules\Director\Models\Camp;

class CampEvaluatorRegistrationService
{
    /**
     * Register or re-register an evaluator for a camp.
     *
     * @param  User  $user
     * @param  array  $data
     * @return array
     */
    public function register(User $user, array $data): array
    {
        if (!$user->hasRole('evaluator')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only evaluators can register for camps.',
                'data'    => [],
            ];
        }

        $campId = $data['camp_id'];

        $camp = Camp::where('id', $campId)
            ->where('status', 'active')
            ->with('director')
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found or inactive.',
                'data'    => [],
            ];
        }

        $existingRegistration = CampEvaluatorRegistration::where('camp_id', $campId)
            ->where('evaluator_id', $user->id)
            ->first();

        if ($existingRegistration) {
            if ($existingRegistration->status === 'pending') {
                return [
                    'success' => false,
                    'code'    => 409,
                    'message' => 'Your registration is already pending approval.',
                    'data'    => [],
                ];
            }

            if ($existingRegistration->status === 'approved') {
                return [
                    'success' => false,
                    'code'    => 409,
                    'message' => 'You are already registered and approved for this camp.',
                    'data'    => [],
                ];
            }

            if ($existingRegistration->status === 'rejected') {
                $existingRegistration->update([
                    'status'            => 'pending',
                    'registration_note' => $data['registration_note'] ?? null,
                    'rejection_reason'  => null,
                    'registered_at'     => now(),
                    'rejected_at'       => null,
                ]);

                $existingRegistration->load(['camp', 'evaluator']);
                $camp->loadMissing('director');

                if ($camp->director?->email) {
                    Mail::to($camp->director->email)->queue(
                        new NewCampRegistrationNotificationForDirector($user, $camp)
                    );
                }

                return [
                    'success' => true,
                    'code'    => 200,
                    'message' => 'Re-registration submitted successfully. Waiting for director approval.',
                    'data'    => [
                        'registration' => new CampEvaluatorRegistrationResource($existingRegistration),
                    ],
                ];
            }
        }

        $registration = CampEvaluatorRegistration::create([
            'camp_id'           => $campId,
            'evaluator_id'      => $user->id,
            'status'            => 'pending',
            'registration_note' => $data['registration_note'] ?? null,
            'registered_at'     => now(),
        ]);

        $registration->load(['camp', 'evaluator']);

        if ($camp->director?->email) {
            Mail::to($camp->director->email)->queue(
                new NewCampRegistrationNotificationForDirector($user, $camp)
            );
        }

        if ($user->email) {
            Mail::to($user->email)->later(
                now()->addSeconds(5),
                new CampRegistrationConfirmationForEvaluator($user, $camp)
            );
        }

        return [
            'success' => true,
            'code'    => 201,
            'message' => 'Registration submitted successfully. Waiting for director approval.',
            'data'    => [
                'registration' => new CampEvaluatorRegistrationResource($registration),
            ],
        ];
    }

    /**
     * Retrieve active/upcoming registrations for the logged-in evaluator.
     *
     * @param  User  $user
     * @param  string|null  $status
     * @param  int  $perPage
     * @return array
     */
    public function getMyRegistrations(User $user, ?string $status = null, int $perPage = 12): array
    {
        if (!$user->hasRole('evaluator')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only evaluators can access this.',
                'data'    => [],
            ];
        }

        $today = now()->toDateString();
        $query = CampEvaluatorRegistration::with(['camp.director', 'approver'])
            ->where('evaluator_id', $user->id);

        if ($status) {
            $query->where('status', $status);
        }

        $registrations = $query->orderBy('registered_at', 'desc')
            ->whereHas('camp', function ($q) use ($today) {
                $q->where('end_date', '>=', $today);
            })
            ->paginate($perPage);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Registrations retrieved successfully.',
            'data'    => [
                'registrations' => CampEvaluatorRegistrationResource::collection($registrations),
                'pagination'    => [
                    'total'        => $registrations->total(),
                    'per_page'     => $registrations->perPage(),
                    'current_page' => $registrations->currentPage(),
                    'last_page'    => $registrations->lastPage(),
                ],
            ],
        ];
    }

    /**
     * Cancel an evaluator registration.
     *
     * @param  User  $user
     * @param  int|string  $registrationId
     * @return array
     */
    public function cancelRegistration(User $user, $registrationId): array
    {
        if (!$user->hasRole('evaluator')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only evaluators can cancel their registrations.',
                'data'    => [],
            ];
        }

        $registration = CampEvaluatorRegistration::where('id', $registrationId)
            ->where('evaluator_id', $user->id)
            ->first();

        if (!$registration) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Registration not found.',
                'data'    => [],
            ];
        }

        if (!in_array($registration->status, ['pending', 'rejected'], true)) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Only pending or rejected registrations can be cancelled.',
                'data'    => [],
            ];
        }

        $registration->delete();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Registration cancelled successfully.',
            'data'    => [],
        ];
    }

    /**
     * Retrieve previous (past) registrations for the logged-in evaluator.
     *
     * @param  User  $user
     * @param  string|null  $status
     * @param  int  $perPage
     * @return array
     */
    public function getPreviousCamps(User $user, ?string $status = null, int $perPage = 12): array
    {
        if (!$user->hasRole('evaluator')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Only evaluators can access this.',
                'data'    => [],
            ];
        }

        $today = now()->toDateString();
        $query = CampEvaluatorRegistration::with(['camp.director', 'approver'])
            ->where('evaluator_id', $user->id);

        if ($status) {
            $query->where('status', $status);
        }

        $registrations = $query->orderBy('registered_at', 'desc')
            ->whereHas('camp', function ($q) use ($today) {
                $q->where('end_date', '<', $today);
            })
            ->paginate($perPage);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Previous retrieved successfully.',
            'data'    => [
                'registrations' => CampEvaluatorRegistrationResource::collection($registrations),
                'pagination'    => [
                    'total'        => $registrations->total(),
                    'per_page'     => $registrations->perPage(),
                    'current_page' => $registrations->currentPage(),
                    'last_page'    => $registrations->lastPage(),
                ],
            ],
        ];
    }
}
