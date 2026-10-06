<?php

namespace App\Http\Controllers\Api\Auth\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\V2\V2RegisterRequest;
use App\Http\Requests\Api\Auth\V2\V2ResendVerificationRequest;
use App\Services\Api\Auth\V2\V2RegisterService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class V2RegisterController extends Controller
{
    use ApiResponse;

    protected V2RegisterService $v2RegisterService;

    public function __construct(V2RegisterService $v2RegisterService)
    {
        parent::__construct();
        $this->v2RegisterService = $v2RegisterService;
    }

    /**
     * User Registration with Email Verification Token
     */
    public function register(V2RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->v2RegisterService->register($request->validated());

            if (!$result['success']) {
                return $this->error(null, $result['message'], $result['code']);
            }

            return $this->success($result['message'], $result['data'], $result['code']);
        } catch (Exception $e) {
            Log::error('Registration failed: ' . $e->getMessage());
            return $this->error(['error' => $e->getMessage()], 'User registration failed', 500);
        }
    }

    /**
     * Verify Email with Token (GET request from email link)
     */
    public function verifyEmail(Request $request): RedirectResponse
    {
        $token = $request->query('token');
        $email = $request->query('email');

        if (!$token || !$email) {
            return $this->redirectToFrontend(
                'error',
                'Invalid verification link.'
            );
        }

        $result = $this->v2RegisterService->verifyEmail($token, $email);

        return $this->redirectToFrontend(
            $result['status'],
            $result['message'],
            $result['data'] ?? []
        );
    }

    /**
     * Resend Verification Email
     */
    public function resendVerification(V2ResendVerificationRequest $request): JsonResponse
    {
        try {
            $result = $this->v2RegisterService->resendVerification($request->email);

            if (!$result['success']) {
                return $this->error(null, $result['message'], $result['code']);
            }

            return $this->success($result['message'], $result['data'], $result['code']);
        } catch (Exception $e) {
            Log::error('Resend verification failed: ' . $e->getMessage());
            return $this->error(null, 'Failed to resend verification email.', 500);
        }
    }

    /**
     * Helper: Redirect to Frontend with Query Parameters
     */
    private function redirectToFrontend(string $status, string $message, array $data = []): RedirectResponse
    {
        $frontendBase = rtrim(config('app.frontend_url'), '/');
        $callbackPath = '/auth/callback';

        $params = array_merge([
            'status'  => $status,
            'message' => $message,
        ], $data);

        $queryString = http_build_query($params);
        $redirectUrl = $frontendBase . $callbackPath . '?' . $queryString;

        Log::info('Redirecting to frontend', [
            'status' => $status,
            'url'    => $redirectUrl,
        ]);

        return redirect()->away($redirectUrl);
    }
}
