<?php

namespace App\Services\Api\Announcement;

use App\Models\Announcement;
use App\Models\AssistantDirectorPermission;
use App\Models\User;
use App\Notifications\AnnouncementNotification;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class AnnouncementService
{
    /**
     * Create announcement, store recipients, and send notifications.
     *
     * @param  User  $director
     * @param  array  $data
     * @return array
     */
    public function storeAnnouncement(User $director, array $data): array
    {
        $campId = (int) $data['camp_id'];

        // Verify this camp exists and is active
        $camp = DB::table('camps')
            ->where('id', $campId)
            ->where('status', 'active')
            ->first();

        if (!$camp) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Camp not found.',
                'data'    => [],
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
                    'message' => 'You can only manage announcements for your own camps.',
                    'data'    => [],
                ];
            }

            if (!$permission->manage_announcements) {
                return [
                    'success' => false,
                    'code'    => 403,
                    'message' => 'You do not have permission to manage announcements for this camp.',
                    'data'    => [],
                ];
            }
        }

        DB::beginTransaction();

        try {
            // Store camp_id on the announcement for proper scoping
            $announcement = Announcement::create([
                'camp_id'         => $campId,
                'created_by'      => $director->id,
                'subject'         => $data['subject'],
                'message'         => $data['message'],
                'announcement_to' => $data['announcement_to'],
                'status'          => 'sent',
                'sent_at'         => now(),
            ]);

            $recipients = $this->getCampRecipients(
                $data['announcement_to'],
                $campId,
                $data['specific_user_ids'] ?? []
            );

            if ($recipients->isEmpty()) {
                DB::rollBack();
                return [
                    'success' => false,
                    'code'    => 404,
                    'message' => 'No recipients found for this announcement',
                    'data'    => [],
                ];
            }

            $recipientData = $recipients->map(fn($user) => [
                'announcement_id' => $announcement->id,
                'user_id'         => $user->id,
                'is_read'         => false,
                'created_at'      => now(),
                'updated_at'      => now(),
            ])->toArray();

            DB::table('announcement_recipients')->insert($recipientData);

            Notification::send($recipients, new AnnouncementNotification($announcement));

            DB::commit();

            return [
                'success' => true,
                'code'    => 201,
                'message' => 'Announcement sent successfully',
                'data'    => [
                    'announcement'     => $announcement,
                    'recipients_count' => $recipients->count(),
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to send announcement: ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Get all announcements created by the director across all camps.
     *
     * @param  User  $director
     * @param  int  $perPage
     * @return array
     */
    public function getMyAnnouncements(User $director, int $perPage = 15): array
    {
        $announcements = Announcement::with(['camp:id,camp_name'])
            ->where('created_by', $director->id)
            ->withCount('recipients')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Announcements fetched successfully',
            'data'    => [
                'announcements' => $announcements->items(),
                'pagination'    => [
                    'total'        => $announcements->total(),
                    'per_page'     => $announcements->perPage(),
                    'current_page' => $announcements->currentPage(),
                    'last_page'    => $announcements->lastPage(),
                ],
            ],
        ];
    }

    /**
     * Delete announcement and associated notifications/recipients.
     *
     * @param  User  $user
     * @param  int|string  $id
     * @return array
     */
    public function deleteAnnouncement(User $user, $id): array
    {
        $announcement = Announcement::find($id);

        if (!$announcement) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Announcement not found',
                'data'    => [],
            ];
        }

        if ($announcement->created_by !== $user->id && !$user->hasRole('director')) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Unauthorized to delete this announcement',
                'data'    => [],
            ];
        }

        DB::beginTransaction();

        try {
            DB::table('notifications')
                ->where('type', 'App\Notifications\AnnouncementNotification')
                ->whereJsonContains('data->announcement_id', $announcement->id)
                ->delete();

            DB::table('announcement_recipients')
                ->where('announcement_id', $announcement->id)
                ->delete();

            $announcement->delete();

            DB::commit();

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Announcement deleted successfully',
                'data'    => [],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Failed to delete announcement: ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Get recipients scoped strictly to this camp — directors are always excluded.
     *
     * @param  string  $announcementTo
     * @param  int  $campId
     * @param  array  $specificUserIds
     * @return Collection
     */
    private function getCampRecipients(string $announcementTo, int $campId, array $specificUserIds = []): Collection
    {
        switch ($announcementTo) {
            case 'all':
                $refereeIds = DB::table('camp_referee_checkins')
                    ->where('camp_id', $campId)
                    ->pluck('referee_id');

                $evaluatorIds = DB::table('camp_evaluator_registrations')
                    ->where('camp_id', $campId)
                    ->where('status', 'approved')
                    ->pluck('evaluator_id');

                $allIds = $refereeIds->merge($evaluatorIds)->unique();

                return $this->buildRecipientQuery($allIds);

            case 'referees':
                $refereeIds = DB::table('camp_referee_checkins')
                    ->where('camp_id', $campId)
                    ->pluck('referee_id');

                return $this->buildRecipientQuery($refereeIds);

            case 'evaluators':
                $evaluatorIds = DB::table('camp_evaluator_registrations')
                    ->where('camp_id', $campId)
                    ->where('status', 'approved')
                    ->pluck('evaluator_id');

                return $this->buildRecipientQuery($evaluatorIds);

            case 'specific':
                $refereeIds = DB::table('camp_referee_checkins')
                    ->where('camp_id', $campId)
                    ->pluck('referee_id');

                $evaluatorIds = DB::table('camp_evaluator_registrations')
                    ->where('camp_id', $campId)
                    ->where('status', 'approved')
                    ->pluck('evaluator_id');

                // Only allow IDs that actually belong to this camp
                $validCampUserIds = $refereeIds->merge($evaluatorIds)->unique();

                $filteredIds = collect($specificUserIds)
                    ->filter(fn($id) => $validCampUserIds->contains($id));

                return $this->buildRecipientQuery($filteredIds);

            default:
                return collect([]);
        }
    }

    /**
     * Build a recipient User query with directors strictly excluded.
     *
     * @param  mixed  $ids
     * @return Collection
     */
    private function buildRecipientQuery($ids): Collection
    {
        return User::whereIn('id', $ids)
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'director');
            })
            ->get();
    }
}
