<?php

namespace App\Http\Controllers\Api\Auth;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\Auth\UpdateAvatarRequest;
use App\Http\Requests\Api\Auth\UpdateProfileRequest;
use App\Models\User;
use App\Services\Api\Auth\UserProfileService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    use ApiResponse;

    protected UserProfileService $userProfileService;

    public function __construct(UserProfileService $userProfileService)
    {
        parent::__construct();
        $this->userProfileService = $userProfileService;
    }

    /**
     * Get User Details
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->error('User not found', 404);
        }

        $response = $this->userProfileService->getUserDetails($user);

        return $this->success(
            'User details fetched successfully',
            $response,
            200
        );
    }

    /**
     * Update User Profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return Helper::jsonResponse(false, 'User not found', 404);
        }

        $response = $this->userProfileService->updateProfile($user, $request->validated());

        return Helper::jsonResponse(true, 'Profile updated successfully', 200, $response);
    }

    /**
     * Update User Avatar
     */
    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return Helper::jsonResponse(false, 'User not found', 404);
        }

        $response = $this->userProfileService->updateAvatar($user, $request->file('avatar'));

        return Helper::jsonResponse(true, 'Avatar updated successfully', 200, $response);
    }

    /**
     * Delete User Profile
     */
    public function destroy(): JsonResponse
    {
        $user = User::findOrFail(auth('api')->id());

        $this->userProfileService->deleteProfile($user);

        return $this->success('User profile deleted successfully', [], 200);
    }

    /**
     * Change User Password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = auth()->guard('api')->user();

        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        $success = $this->userProfileService->changePassword(
            $user,
            $request->old_password,
            $request->new_password
        );

        if (!$success) {
            return $this->error([], 'Old password does not match', 400);
        }

        return $this->success('Password changed successfully', [], 200);
    }
}
