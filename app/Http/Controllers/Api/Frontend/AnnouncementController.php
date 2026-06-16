<?php

namespace App\Http\Controllers\Api\Frontend;

use Exception;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AnnouncementNotification;

class AnnouncementController extends Controller
{
    use ApiResponse;

    /**
     * Make announcement and send notifications
     */
    public function store(Request $request)
    {
        $request->validate([
            'camp_id' => 'required|integer|exists:camps,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'announcement_to' => 'required|in:all,referees,evaluators,specific',
            'specific_user_ids' => 'required_if:announcement_to,specific|array'
        ]);

        $director = auth()->user();

        // Verify this camp belongs to this director and is active
        $camp = DB::table('camps')
            ->where('id', $request->camp_id)
            ->where('director_id', $director->id)
            ->where('status', 'active')
            ->first();

        if (!$camp) {
            return $this->error([], 'Camp not found or you are not authorized for this camp', 403);
        }

        DB::beginTransaction();

        try {
            // Store camp_id on the announcement for proper scoping
            $announcement = Announcement::create([
                'camp_id' => $request->camp_id, // <-- required fix
                'created_by' => $director->id,
                'subject' => $request->subject,
                'message' => $request->message,
                'announcement_to' => $request->announcement_to,
                'status' => 'sent',
                'sent_at' => now()
            ]);

            $recipients = $this->getCampRecipients(
                $request->announcement_to,
                $request->camp_id,
                $request->specific_user_ids ?? []
            );

            if ($recipients->isEmpty()) {
                DB::rollBack();
                return $this->error([], 'No recipients found for this announcement', 404);
            }

            $recipientData = $recipients->map(fn($user) => [
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            DB::table('announcement_recipients')->insert($recipientData);

            Notification::send($recipients, new AnnouncementNotification($announcement));

            DB::commit();

            return $this->success('Announcement sent successfully', [
                'announcement'     => $announcement,
                'recipients_count' => $recipients->count()
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error([], 'Failed to send announcement: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get recipients scoped strictly to this camp — directors are always excluded.
     */
    private function getCampRecipients(string $announcementTo, int $campId, array $specificUserIds = [])
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
     * This prevents directors (of this or any other camp) from receiving
     * announcements even if they appear in checkin/registration tables.
     */
    private function buildRecipientQuery($ids)
    {
        return User::whereIn('id', $ids)
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'director');
            })
            ->get();
    }

    /**
     * Delete announcement
     */
    public function destroy($id)
    {
        $announcement = Announcement::find($id);

        if (!$announcement) {
            return $this->error([], 'Announcement not found', 404);
        }

        if ($announcement->created_by !== auth()->id() && !auth()->user()->hasRole('director')) {
            return $this->error([], 'Unauthorized to delete this announcement', 403);
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

            return $this->success('Announcement deleted successfully', [], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error([], 'Failed to delete announcement: ' . $e->getMessage(), 500);
        }
    }
}
