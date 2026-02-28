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
            'camp_id'           => 'required|integer|exists:camps,id',
            'subject'           => 'required|string|max:255',
            'message'           => 'required|string',
            'announcement_to'   => 'required|in:all,referees,evaluators,specific',
            'specific_user_ids' => 'required_if:announcement_to,specific|array'
        ]);

        $director = auth()->user();

        // Verify if this camp belongs to this director.
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
            $announcement = Announcement::create([
                'created_by'      => $director->id,
                'subject'         => $request->subject,
                'message'         => $request->message,
                'announcement_to' => $request->announcement_to,
                'status'          => 'sent',
                'sent_at'         => now()
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
                'user_id'         => $user->id,
                'is_read'         => false,
                'created_at'      => now(),
                'updated_at'      => now(),
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
     * Get recipients with single camp_id
     */
    private function getCampRecipients($announcementTo, $campId, $specificUserIds = [])
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
                return User::whereIn('id', $allIds)->get();

            case 'referees':
                $refereeIds = DB::table('camp_referee_checkins')
                    ->where('camp_id', $campId)
                    ->pluck('referee_id');

                return User::whereIn('id', $refereeIds)->get();

            case 'evaluators':
                $evaluatorIds = DB::table('camp_evaluator_registrations')
                    ->where('camp_id', $campId)
                    ->where('status', 'approved')
                    ->pluck('evaluator_id');

                return User::whereIn('id', $evaluatorIds)->get();

            case 'specific':
                $refereeIds = DB::table('camp_referee_checkins')
                    ->where('camp_id', $campId)
                    ->pluck('referee_id');

                $evaluatorIds = DB::table('camp_evaluator_registrations')
                    ->where('camp_id', $campId)
                    ->where('status', 'approved')
                    ->pluck('evaluator_id');

                $validCampUserIds = $refereeIds->merge($evaluatorIds)->unique();

                // Only include members of this camp among the requested specific users.
                $filteredIds = collect($specificUserIds)
                    ->filter(fn($id) => $validCampUserIds->contains($id));

                return User::whereIn('id', $filteredIds)->get();

            default:
                return collect([]);
        }
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
            // Delete related notifications
            DB::table('notifications')
                ->where('type', 'App\Notifications\AnnouncementNotification')
                ->whereJsonContains('data->announcement_id', $announcement->id)
                ->delete();

            // announcement_recipients delete
            DB::table('announcement_recipients')
                ->where('announcement_id', $announcement->id)
                ->delete();

            // Soft delete announcement
            $announcement->delete();

            DB::commit();

            return $this->success('Announcement deleted successfully', [], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error([], 'Failed to delete announcement: ' . $e->getMessage(), 500);
        }
    }
}
