<?php

namespace App\Services\Api\Auth;

use App\Mail\ForgotPassOTP;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    /**
     * Send OTP to user email for password reset.
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
                'message' => 'User not found with this email address',
            ];
        }

        $otp = rand(1000, 9999);
        $user->otp            = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(60);
        $user->save();

        Mail::to($email)->queue(new ForgotPassOTP($otp, $user, 'Reset Your Password - Whistle Works'));

        Log::info('Password reset OTP sent to ' . $email . ' at ' . now());

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'OTP sent successfully',
            'data'    => [
                'email'      => $email,
                'expires_at' => $user->otp_expires_at->format('Y-m-d H:i:s'),
                'message'    => 'OTP sent successfully. Please check your email.',
            ],
        ];
    }

    /**
     * Resend password reset OTP.
     *
     * @param  string  $email
     * @param  string  $purpose
     * @return array
     */
    public function resendOtp(string $email, string $purpose = 'verification'): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'User not found with this email address',
            ];
        }

        $otp = rand(1000, 9999);
        $user->otp            = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(60);
        $user->save();

        Mail::to($email)->queue(new ForgotPassOTP($otp, $user, 'Reset Your Password - Whistle Works'));

        Log::info('OTP resent to ' . $email . ' for ' . $purpose . ' at ' . now());

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'OTP resent successfully',
            'data'    => [
                'email'      => $email,
                'expires_at' => $user->otp_expires_at->format('Y-m-d H:i:s'),
                'purpose'    => $purpose,
                'message'    => 'OTP has been resent to your email address.',
            ],
        ];
    }

    /**
     * Verify OTP and exchange for a password reset token.
     *
     * @param  string  $email
     * @param  string  $otp
     * @return array
     */
    public function makeOtpToken(string $email, string $otp): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'User not found',
            ];
        }

        if (Carbon::parse($user->otp_expires_at)->isPast()) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'OTP has expired.',
            ];
        }

        if ((string) $user->otp !== (string) $otp) {
            return [
                'success' => false,
                'code'    => 400,
                'message' => 'Invalid OTP',
            ];
        }

        $token = Str::random(60);

        $user->otp                            = null;
        $user->otp_expires_at                 = null;
        $user->reset_password_token           = $token;
        $user->reset_password_token_expire_at = Carbon::now()->addHour();
        $user->save();

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'OTP verified successfully.',
            'token'   => $token,
        ];
    }

    /**
     * Set new password using verified reset token.
     *
     * @param  string  $email
     * @param  string  $token
     * @param  string  $newPassword
     * @return array
     */
    public function resetPassword(string $email, string $token, string $newPassword): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'User not found',
            ];
        }

        if (
            !empty($user->reset_password_token) &&
            $user->reset_password_token === $token &&
            $user->reset_password_token_expire_at >= Carbon::now()
        ) {
            $user->password                       = Hash::make($newPassword);
            $user->reset_password_token           = null;
            $user->reset_password_token_expire_at = null;
            $user->save();

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Password reset successfully.',
            ];
        }

        return [
            'success' => false,
            'code'    => 419,
            'message' => 'Invalid Token',
        ];
    }
}
