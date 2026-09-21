<?php

namespace App\Services\Api\Notification;

use App\Models\User;

class NotificationService
{
    /**
     * Get paginated notifications for the user with optional type filtering.
     *
     * @param  User  $user
     * @param  int  $perPage
     * @param  string|null  $type
     * @return array
     */
    public function getNotifications(User $user, int $perPage = 15, ?string $type = null): array
    {
        $query = $user->notifications();

        if ($type) {
            $notificationClass = $this->getNotificationClass($type);
            if ($notificationClass) {
                $query->where('type', $notificationClass);
            }
        }

        $notifications = $query->latest()->paginate($perPage);

        $formattedNotifications = $notifications->map(function ($notification) {
            return $this->formatNotification($notification);
        });

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Notifications retrieved successfully',
            'data'    => [
                'notifications' => $formattedNotifications,
                'pagination'    => [
                    'total'        => $notifications->total(),
                    'per_page'     => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page'    => $notifications->lastPage(),
                ],
                'unread_count'  => $user->unreadNotifications()->count(),
            ],
        ];
    }

    /**
     * Get paginated unread notifications for the user with optional type filtering.
     *
     * @param  User  $user
     * @param  int  $perPage
     * @param  string|null  $type
     * @return array
     */
    public function getUnreadNotifications(User $user, int $perPage = 15, ?string $type = null): array
    {
        $query = $user->unreadNotifications();

        if ($type) {
            $notificationClass = $this->getNotificationClass($type);
            if ($notificationClass) {
                $query->where('type', $notificationClass);
            }
        }

        $notifications = $query->latest()->paginate($perPage);

        $formattedNotifications = $notifications->map(function ($notification) {
            return $this->formatNotification($notification);
        });

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Unread notifications retrieved successfully',
            'data'    => [
                'notifications' => $formattedNotifications,
                'pagination'    => [
                    'total'        => $notifications->total(),
                    'per_page'     => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page'    => $notifications->lastPage(),
                ],
            ],
        ];
    }

    /**
     * Get notification counts grouped by type.
     *
     * @param  User  $user
     * @return array
     */
    public function getCounts(User $user): array
    {
        $counts = [
            'total_unread'  => $user->unreadNotifications()->count(),
            'announcements' => $user->unreadNotifications()
                ->where('type', 'App\Notifications\AnnouncementNotification')
                ->count(),
        ];

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Notification counts retrieved',
            'data'    => $counts,
        ];
    }

    /**
     * Show single notification detail and mark as read.
     *
     * @param  User  $user
     * @param  string  $notificationId
     * @return array
     */
    public function showNotification(User $user, string $notificationId): array
    {
        $notification = $user->notifications()
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Notification not found',
                'data'    => [],
            ];
        }

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        $formattedNotification = $this->formatNotification($notification);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Notification retrieved successfully',
            'data'    => $formattedNotification,
        ];
    }

    /**
     * Mark specific notification as read.
     *
     * @param  User  $user
     * @param  string  $notificationId
     * @return array
     */
    public function markAsRead(User $user, string $notificationId): array
    {
        $notification = $user->notifications()
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Notification not found',
                'data'    => [],
            ];
        }

        $notification->markAsRead();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Marked as read',
            'data'    => [],
        ];
    }

    /**
     * Mark all unread notifications as read.
     *
     * @param  User  $user
     * @param  string|null  $type
     * @return array
     */
    public function markAllAsRead(User $user, ?string $type = null): array
    {
        $query = $user->unreadNotifications();

        if ($type) {
            $notificationClass = $this->getNotificationClass($type);
            if ($notificationClass) {
                $query->where('type', $notificationClass);
            }
        }

        $updated = $query->update(['read_at' => now()]);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Notifications marked as read',
            'data'    => [
                'marked_count' => $updated,
            ],
        ];
    }

    /**
     * Delete a single notification.
     *
     * @param  User  $user
     * @param  string  $notificationId
     * @return array
     */
    public function deleteNotification(User $user, string $notificationId): array
    {
        $notification = $user->notifications()
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'Notification not found',
                'data'    => [],
            ];
        }

        $notification->delete();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Notification deleted successfully',
            'data'    => [],
        ];
    }

    /**
     * Clear all read notifications.
     *
     * @param  User  $user
     * @return array
     */
    public function clearRead(User $user): array
    {
        $deleted = $user->readNotifications()->delete();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Read notifications cleared',
            'data'    => [
                'deleted_count' => $deleted,
            ],
        ];
    }

    /**
     * Format notification based on its type.
     *
     * @param  mixed  $notification
     * @return array
     */
    private function formatNotification($notification): array
    {
        $data = $notification->data;

        $formatted = [
            'id'         => $notification->id,
            'type'       => $this->getNotificationType($notification->type),
            'is_read'    => $notification->read_at !== null,
            'read_at'    => $notification->read_at,
            'created_at' => $notification->created_at,
        ];

        switch ($notification->type) {
            case 'App\Notifications\AnnouncementNotification':
                $formatted['data'] = [
                    'announcement_id' => $data['announcement_id'] ?? null,
                    'subject'         => $data['subject'] ?? null,
                    'message'         => $data['message'] ?? null,
                    'created_by'      => $data['created_by'] ?? null,
                    'creator_name'    => $data['creator_name'] ?? 'Director',
                    'sent_at'         => $data['sent_at'] ?? null,
                ];
                break;

            default:
                $formatted['data'] = $data;
                break;
        }

        return $formatted;
    }

    /**
     * Get notification class from type string.
     *
     * @param  string  $type
     * @return string|null
     */
    private function getNotificationClass(string $type): ?string
    {
        $types = [
            'announcement' => 'App\Notifications\AnnouncementNotification',
        ];

        return $types[$type] ?? null;
    }

    /**
     * Get user-friendly type name from notification class.
     *
     * @param  string  $class
     * @return string
     */
    private function getNotificationType(string $class): string
    {
        $types = [
            'App\Notifications\AnnouncementNotification' => 'announcement',
        ];

        return $types[$class] ?? 'general';
    }
}
