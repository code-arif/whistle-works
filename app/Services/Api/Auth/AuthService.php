<?php

namespace App\Services\Api\Auth;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Authenticate user credentials and generate JWT token.
     *
     * @param  string  $email
     * @param  string  $password
     * @return array
     * @throws Exception
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'User not found',
                'data'    => null,
            ];
        }

        if ($user->status !== 'active') {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'User is not active',
                'data'    => null,
            ];
        }

        if (!Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'code'    => 422,
                'message' => 'Invalid credentials',
                'data'    => null,
            ];
        }

        if (!$user->otp_verified_at) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Email not verified. Please verify your email before logging in.',
                'data'    => [
                    'is_otp_verified' => false,
                    'email'           => $email,
                ],
            ];
        }

        // Clear transient OTP / reset tokens
        $user->update([
            'otp'                            => null,
            'otp_expires_at'                 => null,
            'reset_password_token'           => null,
            'reset_password_token_expire_at' => null,
            'last_activity_at'               => now(),
        ]);

        $token = auth('api')->login($user);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Login successful',
            'user'    => $user,
            'token'   => $token,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
    }

    /**
     * Refresh the JWT access token.
     *
     * @return array
     */
    public function refreshToken(): array
    {
        $refreshToken = auth('api')->refresh();

        if (empty($refreshToken)) {
            return [
                'success' => false,
                'code'    => 401,
                'message' => 'Failed to refresh the token.',
            ];
        }

        return [
            'success'    => true,
            'code'       => 200,
            'message'    => 'Access token refreshed successfully.',
            'token'      => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
    }

    /**
     * Log out authenticated user and clean up push tokens.
     *
     * @param  int|null  $userId
     * @return bool
     */
    public function logout(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        Auth::logout('api');
        return true;
    }
}
