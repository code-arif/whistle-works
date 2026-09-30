<?php

namespace Modules\Director\Services\Referee;

use App\Models\AssistantDirectorPermission;
use App\Models\CampPayment;
use App\Models\CampRefereeJearsyNumber;
use App\Models\RefereeEvaluation;
use App\Models\User;
use App\Notifications\RefereeRemovedFromCampNotification;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;

class RefereeService
{
    /**
     * Director manually checks in a referee.
     *
     * @param  mixed $director
     * @param  int   $campId
     * @param  int   $refereeId
     * @return array
     */
    public function directorManualCheckInReferee($director, int $campId, int $refereeId): array
    {
        // Verify camp ownership or assistant director access
        $camp = Camp::forDirectorOrAssistant($director->id)
            ->where('id', $campId)
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        if ($camp->director_id !== $director->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $director->id)
                ->first();

            if (!$permission) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'Unauthorized.',
                    'data'    => null,
                ];
            }

            if (!$permission->manage_roster_referees) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to manual referees for this camp.',
                    'data'    => null,
                ];
            }
        }

        // Find registration record
        $registration = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->with('referee:id,first_name,last_name,email,avatar')
            ->first();

        if (!$registration) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Referee is not registered for this camp.',
                'data'    => [
                    'referee_id'      => $refereeId,
                    'camp_id'         => $campId,
                    'action_required' => 'Referee must register first before checking in.',
                ],
            ];
        }

        // Check if already checked in
        if ($registration->isCheckedIn()) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Referee has already checked in.',
                'data'    => [
                    'registration_id' => $registration->id,
                    'checked_in_at'   => $registration->checked_in_at->format('Y-m-d H:i:s'),
                    'checked_in_by'   => $registration->checked_in_by ?? 'self',
                ],
            ];
        }

        // Validate camp dates
        $today          = now()->toDateString();
        $campStatus     = 'valid';
        $warningMessage = null;

        if ($camp->start_date > $today) {
            $campStatus     = 'not_started';
            $warningMessage = "Warning: Camp hasn't started yet. Check-in date: {$camp->start_date}";
        } elseif ($camp->end_date < $today) {
            $campStatus     = 'ended';
            $warningMessage = "Warning: Camp has ended. End date: {$camp->end_date}";
        }

        DB::beginTransaction();
        try {
            // Update registration to checked-in
            $registration->update([
                'registration_status' => 'checked_in',
                'checked_in_at'       => now(),
                'checked_in_by'       => 'director',
            ]);

            DB::commit();

            // Send notification email to referee (optional)
            $this->sendManualCheckinNotification($registration, $camp, $director);

            Log::info('Director manually checked in referee', [
                'director_id'     => $director->id,
                'camp_id'         => $campId,
                'referee_id'      => $refereeId,
                'registration_id' => $registration->id,
                'checked_in_at'   => $registration->checked_in_at,
            ]);

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Referee successfully checked in manually.',
                'data'    => [
                    'registration' => [
                        'id'            => $registration->id,
                        'status'        => $registration->registration_status,
                        'registered_at' => $registration->registered_at->format('Y-m-d H:i:s'),
                        'checked_in_at' => $registration->checked_in_at->format('Y-m-d H:i:s'),
                        'checked_in_by' => 'director',
                    ],
                    'referee' => [
                        'id'     => $registration->referee->id,
                        'name'   => $registration->referee->first_name . ' ' . $registration->referee->last_name,
                        'email'  => $registration->referee->email,
                        'avatar' => $registration->referee->avatar
                            ? asset($registration->referee->avatar)
                            : asset('default/profile.jpg'),
                    ],
                    'camp' => [
                        'id'         => $camp->id,
                        'name'       => $camp->camp_name,
                        'location'   => $camp->location,
                        'start_date' => $camp->start_date,
                        'end_date'   => $camp->end_date,
                        'status'     => $campStatus,
                    ],
                    'warning' => $warningMessage,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to manually check in referee', [
                'director_id' => $director->id,
                'camp_id'     => $campId,
                'referee_id'  => $refereeId,
                'error'       => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to check in referee.',
                'data'    => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Bulk manual check-in (multiple referees at once).
     *
     * @param  mixed $director
     * @param  int   $campId
     * @param  array $refereeIds
     * @return array
     */
    public function bulkManualCheckIn($director, int $campId, array $refereeIds): array
    {
        // Verify camp ownership
        $camp = Camp::where('id', $campId)
            ->where('director_id', $director->id)
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found or unauthorized.',
                'data'    => null,
            ];
        }

        DB::beginTransaction();
        try {
            $successCount = 0;
            $failedCount  = 0;
            $results      = [];

            foreach ($refereeIds as $refereeId) {
                $registration = CampRefereeCheckin::where('camp_id', $campId)
                    ->where('referee_id', $refereeId)
                    ->where('registration_status', 'registered')
                    ->first();

                if ($registration) {
                    $registration->update([
                        'registration_status' => 'checked_in',
                        'checked_in_at'       => now(),
                        'checked_in_by'       => 'director',
                    ]);

                    $successCount++;
                    $results[] = [
                        'referee_id'    => $refereeId,
                        'status'        => 'success',
                        'checked_in_at' => $registration->checked_in_at->format('Y-m-d H:i:s'),
                    ];
                } else {
                    $failedCount++;
                    $results[] = [
                        'referee_id' => $refereeId,
                        'status'     => 'failed',
                        'reason'     => 'Not registered or already checked in',
                    ];
                }
            }

            DB::commit();

            Log::info('Bulk manual check-in completed', [
                'director_id'   => $director->id,
                'camp_id'       => $campId,
                'success_count' => $successCount,
                'failed_count'  => $failedCount,
            ]);

            return [
                'success' => true,
                'code'    => 200,
                'message' => "Bulk check-in completed. Success: {$successCount}, Failed: {$failedCount}",
                'data'    => [
                    'summary' => [
                        'total_attempted' => count($refereeIds),
                        'success_count'   => $successCount,
                        'failed_count'    => $failedCount,
                    ],
                    'results' => $results,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Bulk manual check-in failed', [
                'director_id' => $director->id,
                'camp_id'     => $campId,
                'error'       => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Bulk check-in failed.',
                'data'    => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Update referee jersey number for a specific camp.
     *
     * @param  mixed       $director
     * @param  int         $campId
     * @param  int         $refereeId
     * @param  string|null $jourcyNumber
     * @return array
     */
    public function updateRefereeJourcyNumber($director, int $campId, int $refereeId, ?string $jourcyNumber): array
    {
        // Find the camp (accessible by director or assistant director)
        $camp = Camp::forDirectorOrAssistant($director->id)
            ->where('id', $campId)
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        if ($camp->director_id !== $director->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $director->id)
                ->first();

            if (!$permission) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'Unauthorized.',
                    'data'    => null,
                ];
            }

            if (!$permission->manage_roster_referees) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to update jersey numbers for this camp.',
                    'data'    => null,
                ];
            }
        }

        // Check if schedule is published
        if ($camp->schedule && $camp->schedule->status === 'published') {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'You cannot change the jersey number because the camp schedule is already published.',
                'data'    => null,
            ];
        }

        // Verify referee is registered for this camp
        $registration = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->with('referee:id,first_name,last_name,email,avatar')
            ->first();

        if (!$registration) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Referee is not registered for this camp.',
                'data'    => [
                    'referee_id' => $refereeId,
                    'camp_id'    => $campId,
                ],
            ];
        }

        // Check if jersey number already exists in THIS CAMP for another referee
        if (!empty($jourcyNumber)) {
            $existingJerseyAssignment = CampRefereeJearsyNumber::where('camp_id', $campId)
                ->where('jersey_number', $jourcyNumber)
                ->where('referee_id', '!=', $refereeId)
                ->with('referee:id,first_name,last_name,email')
                ->first();

            if ($existingJerseyAssignment) {
                $assignedReferee = $existingJerseyAssignment->referee;
                return [
                    'success' => false,
                    'code'    => 400,
                    'message' => 'This jersey number is already assigned to another referee in this camp.',
                    'data'    => [
                        'jourcy_number'     => $jourcyNumber,
                        'assigned_to'       => $assignedReferee->first_name . ' ' . $assignedReferee->last_name,
                        'assigned_to_email' => $assignedReferee->email,
                        'assigned_to_id'    => $assignedReferee->id,
                    ],
                ];
            }
        }

        DB::beginTransaction();
        try {
            $referee = $registration->referee;

            // Get old jersey number for this camp (if exists)
            $oldJerseyAssignment = CampRefereeJearsyNumber::where('camp_id', $campId)
                ->where('referee_id', $refereeId)
                ->first();

            $oldJerseyNumber = $oldJerseyAssignment?->jersey_number;

            // Update or create jersey number for this camp
            $jerseyAssignment = CampRefereeJearsyNumber::updateOrCreate(
                [
                    'camp_id'    => $campId,
                    'referee_id' => $refereeId,
                ],
                [
                    'jersey_number' => $jourcyNumber,
                ]
            );

            DB::commit();

            Log::info('Director updated referee jersey number', [
                'director_id'       => $director->id,
                'camp_id'           => $campId,
                'referee_id'        => $refereeId,
                'old_jersey_number' => $oldJerseyNumber,
                'new_jersey_number' => $jourcyNumber,
                'action'            => $oldJerseyAssignment ? 'updated' : 'created',
            ]);

            return [
                'success' => true,
                'code'    => 200,
                'message' => $oldJerseyAssignment
                    ? 'Jersey number updated successfully.'
                    : 'Jersey number assigned successfully.',
                'data'    => [
                    'referee' => [
                        'id'     => $referee->id,
                        'name'   => $referee->first_name . ' ' . $referee->last_name,
                        'email'  => $referee->email,
                        'avatar' => $referee->avatar ? asset($referee->avatar) : asset('default/profile.jpg'),
                    ],
                    'jersey_assignment' => [
                        'id'                     => $jerseyAssignment->id,
                        'jersey_number'          => $jerseyAssignment->jersey_number,
                        'previous_jersey_number' => $oldJerseyNumber,
                        'assigned_at'            => $jerseyAssignment->created_at->format('Y-m-d H:i:s'),
                        'updated_at'             => $jerseyAssignment->updated_at->format('Y-m-d H:i:s'),
                    ],
                    'registration' => [
                        'id'            => $registration->id,
                        'status'        => $registration->registration_status,
                        'registered_at' => $registration->registered_at->format('Y-m-d H:i:s'),
                        'checked_in_at' => $registration->checked_in_at?->format('Y-m-d H:i:s'),
                    ],
                    'camp' => [
                        'id'   => $camp->id,
                        'name' => $camp->camp_name,
                    ],
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to update referee jersey number', [
                'director_id'   => $director->id,
                'camp_id'       => $campId,
                'referee_id'    => $refereeId,
                'jersey_number' => $jourcyNumber,
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to update jersey number.',
                'data'    => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Remove a referee from a camp (delete registration/check-in & evaluations).
     *
     * @param  mixed $director
     * @param  int   $campId
     * @param  int   $refereeId
     * @return array
     */
    public function removeRefereeFromCamp($director, int $campId, int $refereeId): array
    {
        // Find the camp (accessible by director or assistant director)
        $camp = Camp::forDirectorOrAssistant($director->id)
            ->where('id', $campId)
            ->where('status', 'active')
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => null,
            ];
        }

        if ($camp->director_id !== $director->id) {
            $permission = AssistantDirectorPermission::where('camp_id', $camp->id)
                ->where('assistant_director_id', $director->id)
                ->first();

            if (!$permission) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'Unauthorized.',
                    'data'    => null,
                ];
            }

            if (!$permission->manage_roster_referees) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to remove referees for this camp.',
                    'data'    => null,
                ];
            }
        }

        if ($camp->schedule && $camp->schedule->status === 'published') {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'You cannot remove a referee because the camp schedule is already published.',
                'data'    => [],
            ];
        }

        // Find the registration
        $registration = CampRefereeCheckin::where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->first();

        if (!$registration) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'This referee is not registered for this camp.',
                'data'    => null,
            ];
        }

        $refunded      = false;
        $refundMessage = null;

        // Delete the registration record
        $registration->delete();

        // Delete all evaluations for this referee in this camp
        RefereeEvaluation::where('camp_id', $campId)
            ->where('referee_id', $refereeId)
            ->delete();

        // Send notification email to referee
        $referee = User::find($refereeId);
        if ($referee && $referee->email) {
            try {
                Notification::send(
                    $referee,
                    new RefereeRemovedFromCampNotification($camp, $director)
                );
            } catch (Exception $e) {
                Log::warning('Failed to send cancellation email to referee', [
                    'referee_id' => $referee->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Referee successfully removed from the camp.',
            'data'    => [
                'camp_id'        => $camp->id,
                'camp_name'      => $camp->camp_name,
                'referee_id'     => $refereeId,
                'refunded'       => $refunded,
                'refund_message' => $refundMessage,
                'removed_at'     => now()->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Send notification to referee about manual check-in.
     */
    private function sendManualCheckinNotification($registration, $camp, $director): void
    {
        try {
            $referee = $registration->referee;

            if (class_exists('App\Mail\ManualCheckinNotificationMail')) {
                Log::info('Manual check-in notification sent', [
                    'referee_id'  => $referee->id,
                    'camp_id'     => $camp->id,
                    'director_id' => $director->id,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to send manual check-in notification', [
                'referee_id' => $registration->referee_id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
