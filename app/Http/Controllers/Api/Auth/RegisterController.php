<?php

namespace App\Http\Controllers\Api\Auth;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Auth\ResendOtpRequest;
use App\Http\Requests\Api\Auth\VerifyEmailRequest;
use App\Services\Api\Auth\RegisterService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    use ApiResponse;

    protected RegisterService $registerService;

    public function __construct(RegisterService $registerService)
    {
        parent::__construct();
        $this->registerService = $registerService;
    }

    /**
     * User Registration
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->registerService->register($request->validated());

            auth('api')->login($result['user']);

            return $this->success(
                'User registered successfully. Please verify your email using the OTP sent to your email address.',
                $result['data'],
                201
            );
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('User registration failed', 500, [$e->getMessage()]);
        }
    }

    /**
     * Verify Email with OTP
     */
    public function VerifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        try {
            $result = $this->registerService->verifyEmail(
                (string) $request->input('email'),
                (string) $request->input('otp')
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'code'    => $result['code'],
                ], $result['code']);
            }

            return response()->json([
                'success'    => true,
                'message'    => $result['message'],
                'data'       => $result['data'],
                'token'      => $result['token'],
                'token_type' => $result['token_type'],
                'expires_in' => $result['expires_in'],
                'code'       => 200,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code'    => $e->getCode() ?: 500,
            ], 500);
        }
    }

    /**
     * Resend verification OTP
     */
    public function ResendOtp(ResendOtpRequest $request): JsonResponse
    {
        try {
            $result = $this->registerService->resendOtp((string) $request->input('email'));

            if (!$result['success']) {
                return Helper::jsonErrorResponse($result['message'], $result['code']);
            }

            return response()->json([
                'status'  => true,
                'message' => $result['message'],
                'code'    => 200,
                'otp'     => $result['otp'],
            ], 200);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse($e->getMessage(), 200);
        }
    }
}
