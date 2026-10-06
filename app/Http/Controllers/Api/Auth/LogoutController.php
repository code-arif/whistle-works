<?php

namespace App\Http\Controllers\Api\Auth;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Services\Api\Auth\AuthService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        parent::__construct();
        $this->authService = $authService;
    }

    /**
     * Log out authenticated user and revoke token
     */
    public function logout(): JsonResponse
    {
        try {
            if (!Auth::check('api')) {
                return Helper::jsonErrorResponse('User not authenticated', 401);
            }

            $this->authService->logout(Auth::guard('api')->id());

            return Helper::jsonResponse(true, 'Logged out successfully. Token revoked.', 200);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse($e->getMessage(), 500);
        }
    }
}
