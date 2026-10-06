<?php

namespace App\Http\Controllers\Api\Auth\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\V2\V2ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\V2\V2ResendResetLinkRequest;
use App\Http\Requests\Api\Auth\V2\V2ResetPasswordRequest;
use App\Http\Requests\Api\Auth\V2\V2VerifyResetTokenRequest;
use App\Services\Api\Auth\V2\V2PasswordResetService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class V2ResetPasswordController extends Controller
{
    use ApiResponse;

    protected V2PasswordResetService $passwordResetService;

    public function __construct(V2PasswordResetService $passwordResetService)
    {
        parent::__construct();
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Send Password Reset Link to Email
     */
    public function forgotPassword(V2ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->passwordResetService->forgotPassword($request->email);

            if (!$result['success']) {
                return $this->error(null, $result['message'], $result['code']);
            }

            return $this->success($result['message'], $result['data'], $result['code']);
        } catch (Exception $e) {
            Log::error('Forgot password error: ' . $e->getMessage());
            return $this->error(null, 'Failed to send password reset link. Please try again later.', 500);
        }
    }

    /**
     * Resend Password Reset Link
     */
    public function resendResetLink(V2ResendResetLinkRequest $request): JsonResponse
    {
        try {
            $result = $this->passwordResetService->resendResetLink($request->email);

            if (!$result['success']) {
                return $this->error(null, $result['message'], $result['code']);
            }

            return $this->success($result['message'], $result['data'], $result['code']);
        } catch (Exception $e) {
            Log::error('Resend reset link error: ' . $e->getMessage());
            return $this->error(null, 'Failed to resend reset link.', 500);
        }
    }

    /**
     * Verify Reset Token (Optional - for validating token before showing reset form)
     */
    public function verifyResetToken(V2VerifyResetTokenRequest $request): JsonResponse
    {
        try {
            $result = $this->passwordResetService->verifyResetToken($request->token, $request->email);

            if (!$result['success']) {
                return $this->error(null, $result['message'], $result['code']);
            }

            return $this->success($result['message'], $result['data'], $result['code']);
        } catch (Exception $e) {
            Log::error('Verify reset token error: ' . $e->getMessage());
            return $this->error(null, 'Token verification failed.', 500);
        }
    }

    /**
     * Reset Password with Token
     */
    public function resetPassword(V2ResetPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->passwordResetService->resetPassword(
                $request->email,
                $request->token,
                $request->password
            );

            if (!$result['success']) {
                return $this->error(null, $result['message'], $result['code']);
            }

            return $this->success($result['message'], $result['data'], $result['code']);
        } catch (Exception $e) {
            Log::error('Reset password error: ' . $e->getMessage());
            return $this->error(null, 'Password reset failed.', 500);
        }
    }
}
