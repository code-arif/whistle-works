<?php

namespace Modules\Director\Http\Controllers\Api\Referee;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\CampPayment;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Director\Models\Camp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Mail\ManualCheckinNotificationMail;
use Modules\Director\Models\CampRefereeCheckin;

class RefereeManageController extends Controller
{
    use ApiResponse;

    /**
     * Director manually checks in a referee
     * Use case: Referee registered but forgot to check-in, or has proof of attendance
     */
    public function directorManualCheckInReferee($campId, $refereeId)
    {
        $director = auth('api')->user();

        // Verify camp ownership
        $camp = Camp::where('id', $campId)
            ->where('director_id', $director->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found or you are not authorized.', null, 404);
        }

        // Find registration record
        $registration = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->with('referee:id,first_name,last_name,email,avatar')
            ->first();

        if (!$registration) {
            return $this->error(
                'Referee is not registered for this camp.',
                [
                    'referee_id' => $refereeId,
                    'camp_id' => $campId,
                    'action_required' => 'Referee must register first before checking in.'
                ],
                404
            );
        }

        // Check if already checked in
        if ($registration->isCheckedIn()) {
            return $this->error(
                'Referee has already checked in.',
                [
                    'registration_id' => $registration->id,
                    'checked_in_at' => $registration->checked_in_at->format('Y-m-d H:i:s'),
                    'checked_in_by' => $registration->checked_in_by ?? 'self',
                ],
                400
            );
        }

        // Validate camp dates (optional: allow manual check-in even after camp ends)
        $today = now()->toDateString();
        $campStatus = 'valid';
        $warningMessage = null;

        if ($camp->start_date > $today) {
            $campStatus = 'not_started';
            $warningMessage = "Warning: Camp hasn't started yet. Check-in date: {$camp->start_date}";
        } elseif ($camp->end_date < $today) {
            $campStatus = 'ended';
            $warningMessage = "Warning: Camp has ended. End date: {$camp->end_date}";
        }

        DB::beginTransaction();
        try {
            // Update registration to checked-in
            $registration->update([
                'registration_status' => 'checked_in',
                'checked_in_at' => now(),
                'checked_in_by' => 'director', // Track who checked in (director vs self)
            ]);

            DB::commit();

            // Send notification email to referee (optional)
            $this->sendManualCheckinNotification($registration, $camp, $director);

            Log::info('Director manually checked in referee', [
                'director_id' => $director->id,
                'camp_id' => $campId,
                'referee_id' => $refereeId,
                'registration_id' => $registration->id,
                'checked_in_at' => $registration->checked_in_at
            ]);

            return $this->success(
                'Referee successfully checked in manually.',
                [
                    'registration' => [
                        'id' => $registration->id,
                        'status' => $registration->registration_status,
                        'registered_at' => $registration->registered_at->format('Y-m-d H:i:s'),
                        'checked_in_at' => $registration->checked_in_at->format('Y-m-d H:i:s'),
                        'checked_in_by' => 'director',
                    ],
                    'referee' => [
                        'id' => $registration->referee->id,
                        'name' => $registration->referee->first_name . ' ' . $registration->referee->last_name,
                        'email' => $registration->referee->email,
                        'avatar' => $registration->referee->avatar
                            ? asset($registration->referee->avatar)
                            : asset('default/profile.jpg'),
                    ],
                    'camp' => [
                        'id' => $camp->id,
                        'name' => $camp->camp_name,
                        'location' => $camp->location,
                        'start_date' => $camp->start_date,
                        'end_date' => $camp->end_date,
                        'status' => $campStatus,
                    ],
                    'warning' => $warningMessage,
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to manually check in referee', [
                'director_id' => $director->id,
                'camp_id' => $campId,
                'referee_id' => $refereeId,
                'error' => $e->getMessage()
            ]);

            return $this->error(
                'Failed to check in referee.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Send notification to referee about manual check-in
     */
    private function sendManualCheckinNotification($registration, $camp, $director)
    {
        try {
            $referee = $registration->referee;

            // Check if ManualCheckinNotificationMail exists, if not skip email
            if (class_exists('App\Mail\ManualCheckinNotificationMail')) {
                // Mail::to($referee->email)->send(
                //     new ManualCheckinNotificationMail($referee, $camp, $director, $registration)
                // );

                Log::info('Manual check-in notification sent', [
                    'referee_id' => $referee->id,
                    'camp_id' => $camp->id,
                    'director_id' => $director->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send manual check-in notification', [
                'referee_id' => $registration->referee_id,
                'error' => $e->getMessage()
            ]);
            // Don't fail the check-in if email fails
        }
    }

    /**
     * Bulk manual check-in (multiple referees at once)
     */
    public function bulkManualCheckIn(Request $request, $campId)
    {
        $director = auth('api')->user();

        $request->validate([
            'referee_ids' => 'required|array|min:1',
            'referee_ids.*' => 'required|exists:users,id',
        ]);

        // Verify camp ownership
        $camp = Camp::where('id', $campId)
            ->where('director_id', $director->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found or unauthorized.', null, 404);
        }

        DB::beginTransaction();
        try {
            $successCount = 0;
            $failedCount = 0;
            $results = [];

            foreach ($request->referee_ids as $refereeId) {
                $registration = CampRefereeCheckin::where('camp_id', $campId)
                    ->where('referee_id', $refereeId)
                    ->where('registration_status', 'registered')
                    ->first();

                if ($registration) {
                    $registration->update([
                        'registration_status' => 'checked_in',
                        'checked_in_at' => now(),
                        'checked_in_by' => 'director',
                    ]);

                    $successCount++;
                    $results[] = [
                        'referee_id' => $refereeId,
                        'status' => 'success',
                        'checked_in_at' => $registration->checked_in_at->format('Y-m-d H:i:s'),
                    ];
                } else {
                    $failedCount++;
                    $results[] = [
                        'referee_id' => $refereeId,
                        'status' => 'failed',
                        'reason' => 'Not registered or already checked in',
                    ];
                }
            }

            DB::commit();

            Log::info('Bulk manual check-in completed', [
                'director_id' => $director->id,
                'camp_id' => $campId,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
            ]);

            return $this->success(
                "Bulk check-in completed. Success: {$successCount}, Failed: {$failedCount}",
                [
                    'summary' => [
                        'total_attempted' => count($request->referee_ids),
                        'success_count' => $successCount,
                        'failed_count' => $failedCount,
                    ],
                    'results' => $results,
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk manual check-in failed', [
                'director_id' => $director->id,
                'camp_id' => $campId,
                'error' => $e->getMessage()
            ]);

            return $this->error(
                'Bulk check-in failed.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update jourcy number for a referee
     */
    /**
     * Update referee's jourcy number (Director only)
     * Director can assign/update jourcy number for registered referees
     */
    public function updateRefereeJourcyNumber(Request $request, $campId, $refereeId)
    {
        $director = auth('api')->user();

        $request->validate([
            'jourcy_number' => 'nullable|string|max:3',
        ]);

        // Verify camp ownership
        $camp = Camp::where('id', $campId)
            ->where('director_id', $director->id)
            ->first();

        if (!$camp) {
            return $this->error('Camp not found or unauthorized.', null, 404);
        }

        // Verify referee is registered for this camp
        $registration = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->with('referee:id,first_name,last_name,email,jourcy_number')
            ->first();

        if (!$registration) {
            return $this->error(
                'Referee is not registered for this camp.',
                [
                    'referee_id' => $refereeId,
                    'camp_id' => $campId,
                ],
                404
            );
        }

        // Check if jourcy number already exists for another user
        // $existingUser = User::where('jourcy_number', $request->jourcy_number)
        //     ->where('id', '!=', $refereeId)
        //     ->first();

        // if ($existingUser) {
        //     return $this->error(
        //         [
        //             'jourcy_number' => $request->jourcy_number,
        //             'assigned_to' => $existingUser->first_name . ' ' . $existingUser->last_name,
        //             'assigned_to_email' => $existingUser->email,
        //         ],
        //         'This jourcy number is already assigned to another referee.',
        //         400
        //     );
        // }

        DB::beginTransaction();
        try {
            $referee = $registration->referee;
            $oldJourcyNumber = $referee->jourcy_number;

            // Update jourcy number in users table
            $referee->update([
                'jourcy_number' => $request->jourcy_number,
            ]);

            DB::commit();

            Log::info('Director updated referee jourcy number', [
                'director_id' => $director->id,
                'camp_id' => $campId,
                'referee_id' => $refereeId,
                'old_jourcy_number' => $oldJourcyNumber,
                'new_jourcy_number' => $request->jourcy_number,
            ]);

            return $this->success(
                'Jourcy number updated successfully.',
                [
                    'referee' => [
                        'id' => $referee->id,
                        'name' => $referee->first_name . ' ' . $referee->last_name,
                        'email' => $referee->email,
                        'jourcy_number' => $referee->jourcy_number,
                        'previous_jourcy_number' => $oldJourcyNumber,
                    ],
                    'registration' => [
                        'id' => $registration->id,
                        'status' => $registration->registration_status,
                        'registered_at' => $registration->registered_at->format('Y-m-d H:i:s'),
                        'checked_in_at' => $registration->checked_in_at?->format('Y-m-d H:i:s'),
                    ],
                    'camp' => [
                        'id' => $camp->id,
                        'name' => $camp->camp_name,
                    ],
                ],
                200
            );
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to update referee jourcy number', [
                'director_id' => $director->id,
                'camp_id' => $campId,
                'referee_id' => $refereeId,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                ['error' => $e->getMessage()],
                'Failed to update jourcy number.',
                500
            );
        }
    }


    /**
     * Director: Remove a referee from a camp (delete registration/check-in)
     */
    public function removeRefereeFromCamp(Request $request, $campId, $refereeId)
    {
        $director = auth('api')->user();

        // Find the camp and verify ownership
        $camp = Camp::where('id', $campId)
            ->where('director_id', $director->id) // Important: only his own camp
            ->where('status', 'active')
            ->first();

        if (!$camp) {
            return $this->error('Camp not found or you are not authorized to manage this camp.', null, 404);
        }

        // Find the registration
        $registration = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->first();

        if (!$registration) {
            return $this->error('This referee is not registered for this camp.', null, 404);
        }

        // Optional: Prevent removal after check-in
        // if ($registration->registration_status === 'checked_in') {
        //     return $this->error('Cannot remove a referee who has already checked in.', null, 403);
        // }

        // Optional: Refund logic (Stripe refund)
        $refunded = false;
        $refundMessage = null;

        $payment = CampPayment::where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->where('status', 'succeeded')
            ->first();

        // if ($payment && $payment->stripe_payment_intent_id) {
        //     try {
        //         $refundResult = $this->stripeService->refundPayment($payment->stripe_payment_intent_id);

        //         if ($refundResult['success']) {
        //             $payment->update([
        //                 'status' => 'refunded',
        //                 'refunded_at' => now(),
        //             ]);
        //             $refunded = true;
        //         } else {
        //             $refundMessage = $refundResult['error'] ?? 'Refund failed';
        //             // তবুও registration delete করবো? নাকি stop করবো?
        //             // এখানে তুমি decide করো। আমি delete করছি, কিন্তু message দিচ্ছি
        //         }
        //     } catch (Exception $e) {
        //         Log::error('Stripe refund failed during referee removal', [
        //             'payment_id' => $payment->id,
        //             'error' => $e->getMessage()
        //         ]);
        //         $refundMessage = 'Refund attempt failed: ' . $e->getMessage();
        //     }
        // }

        // Delete the registration record
        $registration->delete();

        // Optional: Send notification email to referee
        $referee = User::find($refereeId);
        if ($referee && $referee->email) {
            try {
                // Mail::to($referee->email)->send(
                //     new CampRegistrationCancelledMail($director, $referee, $camp, $refunded)
                // );
            } catch (Exception $e) {
                Log::warning('Failed to send cancellation email to referee', [
                    'referee_id' => $referee->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $this->success(
            'Referee successfully removed from the camp.',
            [
                'camp_id' => $camp->id,
                'camp_name' => $camp->camp_name,
                'referee_id' => $refereeId,
                'refunded' => $refunded,
                'refund_message' => $refundMessage,
                'removed_at' => now()->format('Y-m-d H:i:s'),
            ],
            200
        );
    }
}
