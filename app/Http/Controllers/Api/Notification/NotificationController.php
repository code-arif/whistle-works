<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Services\Api\Notification\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications for authenticated user.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();

        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $perPage = (int) $request->input('per_page', 15);
        $type = $request->input('type');

        $result = $this->notificationService->getNotifications($user, $perPage, $type);

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get only unread notifications.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function unread(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();

        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $perPage = (int) $request->input('per_page', 15);
        $type = $request->input('type');

        $result = $this->notificationService->getUnreadNotifications($user, $perPage, $type);

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get notification counts by type.
     *
     * @return JsonResponse
     */
    public function counts(): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();

        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->notificationService->getCounts($user);

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get single notification detail and mark as read.
     *
     * @param  string  $notificationId
     * @return JsonResponse
     */
    public function show($notificationId): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();

        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->notificationService->showNotification($user, (string) $notificationId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Mark specific notification as read.
     *
     * @param  string  $notificationId
     * @return JsonResponse
     */
    public function markAsRead($notificationId): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();

        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->notificationService->markAsRead($user, (string) $notificationId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Mark all notifications as read.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();

        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $type = $request->input('type');
        $result = $this->notificationService->markAllAsRead($user, $type);

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Delete a single notification.
     *
     * @param  string  $notificationId
     * @return JsonResponse
     */
    public function destroy($notificationId): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();

        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->notificationService->deleteNotification($user, (string) $notificationId);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Clear all read notifications.
     *
     * @return JsonResponse
     */
    public function clearRead(): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();

        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->notificationService->clearRead($user);

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
