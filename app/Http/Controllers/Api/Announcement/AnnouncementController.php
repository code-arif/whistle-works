<?php

namespace App\Http\Controllers\Api\Announcement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Announcement\StoreAnnouncementRequest;
use App\Services\Api\Announcement\AnnouncementService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    use ApiResponse;

    protected AnnouncementService $announcementService;

    public function __construct(AnnouncementService $announcementService)
    {
        $this->announcementService = $announcementService;
    }

    /**
     * Make announcement and send notifications.
     *
     * @param  StoreAnnouncementRequest  $request
     * @return JsonResponse
     */
    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $director = auth('api')->user() ?? auth()->user();

        if (!$director) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->announcementService->storeAnnouncement($director, $request->validated());

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Get all announcements created by the authenticated director.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function myAnnouncements(Request $request): JsonResponse
    {
        $director = auth('api')->user() ?? auth()->user();

        if (!$director) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $perPage = (int) $request->get('per_page', 15);
        $result = $this->announcementService->getMyAnnouncements($director, $perPage);

        return $this->success($result['message'], $result['data'], $result['code']);
    }

    /**
     * Delete announcement.
     *
     * @param  int|string  $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $user = auth('api')->user() ?? auth()->user();

        if (!$user) {
            return $this->error([], 'Unauthenticated.', 401);
        }

        $result = $this->announcementService->deleteAnnouncement($user, $id);

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
