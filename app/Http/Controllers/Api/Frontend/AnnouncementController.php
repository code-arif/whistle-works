<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Models\User;
use App\Models\Announcement;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AnnouncementRecipient;

class AnnouncementController extends Controller
{
    use ApiResponse;
    /**
     * Make announcement
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'announcement_to' => 'required|in:all,referees,evaluators,specific',
            'specific_user_ids' => 'required_if:announcement_to,specific|array'
        ]);

        $announcement = Announcement::create([
            'created_by' => auth()->id(),
            'subject' => $request->subject,
            'message' => $request->message,
            'announcement_to' => $request->announcement_to,
            'status' => 'sent',
            'sent_at' => now()
        ]);

        // Recipients determine kora
        $recipients = $this->getRecipients($request->announcement_to, $request->specific_user_ids);

        // Recipients table e insert kora
        foreach ($recipients as $userId) {
            AnnouncementRecipient::create([
                'announcement_id' => $announcement->id,
                'user_id' => $userId
            ]);
        }

        return $this->success('Announcement sent successfully', $announcement, 201);
    }

    /**
     * Format receipent
     */
    private function getRecipients($announcementTo, $specificUserIds = [])
    {
        switch ($announcementTo) {
            case 'all':
                return User::pluck('id')->toArray();

            case 'referees':
                return User::role('referee')->pluck('id')->toArray();

            case 'evaluators':
                return User::role('evaluator')->pluck('id')->toArray();

            case 'specific':
                return $specificUserIds;

            default:
                return [];
        }
    }

    /**
     * User tar notifications dekhar jonno
     */
    public function myAnnouncements(Request $request)
    {
        $announcements = Announcement::whereHas('recipients', function ($query) {
            $query->where('user_id', auth('api')->id());
        })
            ->with(['creator' => function ($query) {
                $query->select('id',);
            }])
            ->latest()
            ->paginate($request->input('per_page', 10));

        $response = [
            'announcements' => $announcements->items(),
            'pagination' => [
                'total' => $announcements->total(),
                'per_page' => $announcements->perPage(),
                'current_page' => $announcements->currentPage(),
                'last_page' => $announcements->lastPage(),
            ]
        ];

        return $this->success('Announcement retrieved successfully !', $response, 200);
    }

    /**
     * Mark as read
     */
    public function markAsRead($announcementId)
    {
        $recipient = AnnouncementRecipient::where('announcement_id', $announcementId)
            ->where('user_id', auth()->id())
            ->first();

        if ($recipient) {
            $recipient->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        }

        return $this->success('Marked as read', [], 200);
    }

    // Bulk mark as read - user er sob unread announcements read kore dibe
    public function markAllAsRead()
    {
        $userId = auth('api')->id();

        $updated = AnnouncementRecipient::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return $this->success('All announcements marked as read', $updated, 201);
    }

    // Delete announcement - only creator/director can delete
    public function destroy($id)
    {
        $announcement = Announcement::find($id);

        if (!$announcement) {
            return $this->error([], 'Announcement not found', 404);
        }

        // Check if user is creator or has director role
        if ($announcement->created_by !== auth('api')->id() && !auth('api')->user()->hasRole('director')) {
            return $this->error([], 'Unauthorized to delete this announcement', 403);
        }

        $announcement->delete(); // soft delete

        return $this->success('Announcement deleted successfully', [], 200);
    }
}
