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
                $query->select('id',); // অথবা 'name', 'email' যা দরকার
            }])
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $announcements->items(),
            'pagination' => [
                'total' => $announcements->total(),
                'per_page' => $announcements->perPage(),
                'current_page' => $announcements->currentPage(),
                'last_page' => $announcements->lastPage(),
            ]
        ]);
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

        return response()->json(['message' => 'Marked as read']);
    }
}
