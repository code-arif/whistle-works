<?php

namespace App\Services\Api\Auth\V2;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class V2PasswordResetService
{
    /**
     * Send password reset link to user's email.
     *
     * @param  string  $email
     * @return array
     */
    public function forgotPassword(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'User not found with this email address.',
            ];
        }

        if (!$user->otp_verified_at) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Please verify your email first before resetting password.',
            ];
        }

        $resetToken  = Str::random(64);
        $tokenExpiry = Carbon::now()->addHours(config('auth.password_reset_token_expiry', 1));

        $user->update([
            'reset_password_token'           => hash('sha256', $resetToken),
            'reset_password_token_expire_at' => $tokenExpiry,
        ]);

        $resetUrl = $this->generateResetUrl($resetToken, $user->email);
        Mail::to($user->email)->queue(new PasswordResetMail($user, $resetUrl));

        Log::info('Password reset link sent to: ' . $user->email);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Password reset link has been sent to your email address. The link will expire in 1 hour.',
            'data'    => [
                'email'      => $user->email,
                'expires_at' => $tokenExpiry->toDateTimeString(),
            ],
        ];
    }

    /**
     * Resend password reset link.
     *
     * @param  string  $email
     * @return array
     */
    public function resendResetLink(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'User not found.',
            ];
        }

        if (!$user->otp_verified_at) {
            return [
                'success' => false,
                'code'    => 403,
                'message' => 'Please verify your email first.',
            ];
        }

        $resetToken  = Str::random(64);
        $tokenExpiry = Carbon::now()->addHours(config('auth.password_reset_token_expiry', 1));

        $user->update([
            'reset_password_token'           => hash('sha256', $resetToken),
            'reset_password_token_expire_at' => $tokenExpiry,
        ]);

        $resetUrl = $this->generateResetUrl($resetToken, $user->email);
        Mail::to($user->email)->queue(new PasswordResetMail($user, $resetUrl));

        Log::info('Password reset link resent to: ' . $user->email);

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Password reset link has been resent to your email address.',
            'data'    => [
                'email'      => $user->email,
                'expires_at' => $tokenExpiry->toDateTimeString(),
            ],
        ];
    }

    /**
     * Validate token before showing reset form.
     *
     * @param  string  $token
     * @param  string  $email
     * @return array
     */
    public function verifyResetToken(string $token, string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'User not found.',
            ];
        }

        $hashedToken = hash('sha256', $token);

        if ($user->reset_password_token !== $hashedToken) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Invalid or expired reset token.',
            ];
        }

        if (Carbon::parse($user->reset_password_token_expire_at)->isPast()) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Password reset link has expired. Please request a new one.',
            ];
        }

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Reset token is valid.',
            'data'    => ['valid' => true],
        ];
    }

    /**
     * Reset password with valid token.
     *
     * @param  string  $email
     * @param  string  $token
     * @param  string  $password
     * @return array
     */
    public function resetPassword(string $email, string $token, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'User not found.',
            ];
        }

        $hashedToken = hash('sha256', $token);

        if ($user->reset_password_token !== $hashedToken) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Invalid reset token.',
            ];
        }

        if (Carbon::parse($user->reset_password_token_expire_at)->isPast()) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Password reset link has expired. Please request a new one.',
            ];
        }

        $user->update([
            'password'                         => Hash::make($password),
            'reset_password_token'             => null,
            'reset_password_token_expire_at'   => null,
        ]);

        Log::info('Password reset successfully for: ' . $user->email);

        $token     = auth('api')->login($user);
        $expiresIn = auth('api')->factory()->getTTL() * 60;

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'Password has been reset successfully.',
            'data'    => [
                'user' => [
                    'id'         => $user->id,
                    'email'      => $user->email,
                    'username'   => $user->username,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                ],
                'token'      => $token,
                'token_type' => 'bearer',
                'expires_in' => $expiresIn,
            ],
        ];
    }

    /**
     * Helper: Generate Reset URL
     */
    public function generateResetUrl(string $token, string $email): string
    {
        $frontendUrl = config('app.frontend_url');
        return "{$frontendUrl}/reset-password?token={$token}&email=" . urlencode($email);
    }
}
