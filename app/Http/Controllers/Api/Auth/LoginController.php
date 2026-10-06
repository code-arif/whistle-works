<?php

namespace App\Http\Controllers\Api\Auth;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\Api\Auth\AuthUserResource;
use App\Services\Api\Auth\AuthService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        parent::__construct();
        $this->authService = $authService;
    }

    /**
     * User Login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                (string) $request->email,
                (string) $request->password
            );

            if (!$result['success']) {
                return $this->error($result['data'], $result['message'], $result['code']);
            }

            return $this->success($result['message'], [
                'user'       => (new AuthUserResource($result['user']))->resolve(),
                'token'      => $result['token'],
                'token_type' => 'bearer',
                'expires_in' => $result['expires_in'],
            ]);
        } catch (Exception $e) {
            return $this->error(['error' => $e->getMessage()], 'An error occurred during login', 500);
        }
    }

    /**
     * Refresh JWT access token
     */
    public function refreshToken(): JsonResponse
    {
        $result = $this->authService->refreshToken();

        if (!$result['success']) {
            return Helper::jsonErrorResponse($result['message'], $result['code']);
        }

        return response()->json([
            'status'     => true,
            'message'    => $result['message'],
            'code'       => 200,
            'token_type' => $result['token_type'],
            'token'      => $result['token'],
            'expires_in' => $result['expires_in'],
        ], 200);
    }
}
