<?php

namespace App\Http\Controllers\Api\Auth;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\MakeOtpTokenRequest;
use App\Http\Requests\Api\Auth\ResendOtpRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Services\Api\Auth\PasswordResetService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ResetPasswordController extends Controller
{
    use ApiResponse;

    protected PasswordResetService $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        parent::__construct();
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Send OTP to user email for password reset.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->passwordResetService->forgotPassword((string) $request->input('email'));

            if (!$result['success']) {
                return $this->error($result['message'], $result['code']);
            }

            return $this->success($result['message'], $result['data'], 200);
        } catch (Exception $e) {
            Log::error('Forgot password error: ' . $e->getMessage());
            return $this->error('Failed to send OTP. Please try again later.', 500);
        }
    }

    /**
     * Resend OTP to user email for password reset.
     */
    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        try {
            $purpose = (string) $request->input('purpose', 'verification');
            $result  = $this->passwordResetService->resendOtp((string) $request->input('email'), $purpose);

            if (!$result['success']) {
                return $this->error($result['message'], $result['code']);
            }

            return $this->success($result['message'], $result['data'], 200);
        } catch (Exception $e) {
            Log::error('Resend OTP error: ' . $e->getMessage());
            return $this->error('Failed to resend OTP. Please try again later.', 500);
        }
    }

    /**
     * Verify OTP and receive reset token.
     */
    public function MakeOtpToken(MakeOtpTokenRequest $request): JsonResponse
    {
        try {
            $result = $this->passwordResetService->makeOtpToken(
                (string) $request->input('email'),
                (string) $request->input('otp')
            );

            if (!$result['success']) {
                return Helper::jsonErrorResponse($result['message'], $result['code']);
            }

            return response()->json([
                'status'  => true,
                'message' => $result['message'],
                'code'    => 200,
                'token'   => $result['token'],
            ], 200);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Set new password using verified reset token.
     */
    public function ResetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->passwordResetService->resetPassword(
                (string) $request->input('email'),
                (string) $request->input('token'),
                (string) $request->input('password')
            );

            if (!$result['success']) {
                return Helper::jsonErrorResponse($result['message'], $result['code']);
            }

            return Helper::jsonResponse(true, $result['message'], 200);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse($e->getMessage(), 500);
        }
    }
}
