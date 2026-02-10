<?php

namespace App\Http\Controllers\Api\Auth\V2;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Helpers\Helper;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class V2ResetPasswordController extends Controller
{
    use ApiResponse;

    public $select;

    public function __construct()
    {
        parent::__construct();
        $this->select = ['id', 'first_name', 'last_name', 'email', 'avatar'];
    }

    /**
     * Send Password Reset Link to Email
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $user = User::where('email', $request->input('email'))->first();

            if (!$user) {
                return $this->error(null, 'User not found with this email address.', 404);
            }

            // Check if email is verified
            if (!$user->otp_verified_at) {
                return $this->error(
                    null,
                    'Please verify your email first before resetting password.',
                    403
                );
            }

            // Generate password reset token
            $resetToken = Str::random(64);
            $tokenExpiry = Carbon::now()->addHours(config('auth.password_reset_token_expiry', 1));

            // Update user with reset token
            $user->update([
                'reset_password_token'             => hash('sha256', $resetToken),
                'reset_password_token_expire_at'   => $tokenExpiry,
            ]);

            // Generate reset URL
            $resetUrl = $this->generateResetUrl($resetToken, $user->email);

            // Send password reset email
            Mail::to($user->email)->queue(new PasswordResetMail($user, $resetUrl));

            Log::info('Password reset link sent to: ' . $user->email);

            return $this->success(
                'Password reset link has been sent to your email address. The link will expire in 1 hour.',
                [
                    'email'      => $user->email,
                    'expires_at' => $tokenExpiry->toDateTimeString(),
                ],
                200
            );

        } catch (Exception $e) {
            Log::error('Forgot password error: ' . $e->getMessage());
            return $this->error(null, 'Failed to send password reset link. Please try again later.', 500);
        }
    }

    /**
     * Resend Password Reset Link
     */
    public function resendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $user = User::where('email', $request->input('email'))->first();

            if (!$user) {
                return $this->error(null, 'User not found.', 404);
            }

            if (!$user->otp_verified_at) {
                return $this->error(null, 'Please verify your email first.', 403);
            }

            // Generate new reset token
            $resetToken = Str::random(64);
            $tokenExpiry = Carbon::now()->addHours(config('auth.password_reset_token_expiry', 1));

            $user->update([
                'reset_password_token'             => hash('sha256', $resetToken),
                'reset_password_token_expire_at'   => $tokenExpiry,
            ]);

            // Generate reset URL
            $resetUrl = $this->generateResetUrl($resetToken, $user->email);

            // Send email
            Mail::to($user->email)->queue(new PasswordResetMail($user, $resetUrl));

            Log::info('Password reset link resent to: ' . $user->email);

            return $this->success(
                'Password reset link has been resent to your email address.',
                [
                    'email'      => $user->email,
                    'expires_at' => $tokenExpiry->toDateTimeString(),
                ],
                200
            );

        } catch (Exception $e) {
            Log::error('Resend reset link error: ' . $e->getMessage());
            return $this->error(null, 'Failed to resend reset link.', 500);
        }
    }

    /**
     * Verify Reset Token (Optional - for validating token before showing reset form)
     */
    public function verifyResetToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $user = User::where('email', $request->input('email'))->first();

            if (!$user) {
                return $this->error(null, 'User not found.', 404);
            }

            // Check token
            $hashedToken = hash('sha256', $request->input('token'));

            if ($user->reset_password_token !== $hashedToken) {
                return $this->error(null, 'Invalid or expired reset token.', 400);
            }

            // Check expiry
            if (Carbon::parse($user->reset_password_token_expire_at)->isPast()) {
                return $this->error(null, 'Password reset link has expired. Please request a new one.', 400);
            }

            return $this->success('Reset token is valid.', ['valid' => true], 200);

        } catch (Exception $e) {
            Log::error('Verify reset token error: ' . $e->getMessage());
            return $this->error(null, 'Token verification failed.', 500);
        }
    }

    /**
     * Reset Password with Token
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'token'    => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            $user = User::where('email', $request->input('email'))->first();

            if (!$user) {
                return $this->error(null, 'User not found.', 404);
            }

            // Verify token
            $hashedToken = hash('sha256', $request->input('token'));

            if ($user->reset_password_token !== $hashedToken) {
                return $this->error(null, 'Invalid reset token.', 400);
            }

            // Check token expiry
            if (Carbon::parse($user->reset_password_token_expire_at)->isPast()) {
                return $this->error(null, 'Password reset link has expired. Please request a new one.', 400);
            }

            // Update password
            $user->update([
                'password'                         => Hash::make($request->input('password')),
                'reset_password_token'             => null,
                'reset_password_token_expire_at'   => null,
            ]);

            Log::info('Password reset successfully for: ' . $user->email);

            // Auto-login user
            $token = auth('api')->login($user);
            $expiresIn = auth('api')->factory()->getTTL() * 60;

            return $this->success(
                'Password has been reset successfully.',
                [
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
                200
            );

        } catch (Exception $e) {
            Log::error('Reset password error: ' . $e->getMessage());
            return $this->error(null, 'Password reset failed.', 500);
        }
    }

    /**
     * Helper: Generate Reset URL
     */
    private function generateResetUrl($token, $email)
    {
        $frontendUrl = config('app.frontend_url');
        return "{$frontendUrl}/reset-password?token={$token}&email=" . urlencode($email);
    }
}
