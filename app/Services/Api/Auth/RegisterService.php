<?php

namespace App\Services\Api\Auth;

use App\Mail\AdminRegistrationMail;
use App\Mail\OtpMail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RegisterService
{
    /**
     * Register a new user with OTP email verification.
     *
     * @param  array  $data
     * @return array
     * @throws Exception
     */
    public function register(array $data): array
    {
        DB::beginTransaction();

        try {
            do {
                $slug = $data['first_name'] . rand(1000000000, 9999999999);
            } while (User::where('slug', $slug)->exists());

            $username = '@' . strtolower($data['first_name']) . '_' . $this->randomAlphaNum(4);

            $user = User::create([
                'first_name'                => $data['first_name'],
                'last_name'                 => $data['last_name'],
                'address'                   => $data['address'],
                'username'                  => $username,
                'slug'                      => $slug,
                'email'                     => strtolower($data['email']),
                'password'                  => Hash::make($data['password']),
                'otp'                       => rand(1000, 9999),
                'otp_expires_at'            => Carbon::now()->addMinutes(60),
                'status'                    => 'active',
                'last_activity_at'          => Carbon::now(),
                'biography'                 => $data['biography'] ?? null,
                'phone'                     => $data['phone'],
                'receive_sms_notifications' => !empty($data['receive_sms_notifications']),
            ]);

            DB::table('model_has_roles')->insert([
                'role_id'    => $data['role'],
                'model_type' => 'App\Models\User',
                'model_id'   => $user->id,
            ]);

            // Notify Admin
            $notiData = [
                'user_id'  => $user->id,
                'title'    => 'User register in successfully.',
                'body'     => 'User register in successfully.',
                'name'     => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'username' => $user->username,
                'email'    => $user->email,
                'phone'    => $user->phone,
                'role'     => $user->role,
                'status'   => $user->status,
            ];

            try {
                Mail::to('drewbontrager@gmail.com')->queue(new AdminRegistrationMail($notiData));
            } catch (Exception $e) {
                Log::error('Admin registration notification failed: ' . $e->getMessage());
            }

            // Send OTP email to user
            Mail::to($user->email)->queue(new OtpMail($user->otp, $user, 'Verify Your Email Address'));

            DB::commit();

            return [
                'success' => true,
                'user'    => $user,
                'data'    => [
                    'first_name'                => $user->first_name,
                    'last_name'                 => $user->last_name,
                    'username'                  => $user->username,
                    'email'                     => $user->email,
                    'phone'                     => $user->phone,
                    'address'                   => $user->address,
                    'avatar'                    => $user->avatar,
                    'role'                      => $user->role,
                    'biography'                 => $user->biography,
                    'receive_sms_notifications' => (bool) $user->receive_sms_notifications,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Verify user email via 4-digit OTP.
     *
     * @param  string  $email
     * @param  string  $otp
     * @return array
     */
    public function verifyEmail(string $email, string $otp): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'User not found.',
            ];
        }

        if (!empty($user->otp_verified_at)) {
            return [
                'success' => false,
                'code'    => 409,
                'message' => 'Email already verified.',
            ];
        }

        if ((string) $user->otp !== (string) $otp) {
            return [
                'success' => false,
                'code'    => 422,
                'message' => 'Invalid OTP code',
            ];
        }

        if (Carbon::parse($user->otp_expires_at)->isPast()) {
            return [
                'success' => false,
                'code'    => 422,
                'message' => 'OTP has expired. Please request a new OTP.',
            ];
        }

        $user->otp_verified_at = now();
        $user->otp             = null;
        $user->otp_expires_at  = null;
        $user->save();

        try {
            Mail::to($user->email)->send(new WelcomeMail($user));
        } catch (Exception $mailException) {
            Log::error('Welcome email failed to send: ' . $mailException->getMessage());
        }

        $token     = auth('api')->login($user);
        $expiresIn = auth('api')->factory()->getTTL() * 60;

        return [
            'success'    => true,
            'code'       => 200,
            'message'    => 'Email verified successfully.',
            'user'       => $user,
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => $expiresIn,
            'data'       => [
                'id'         => $user->id,
                'email'      => $user->email,
                'username'   => $user->username,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'avatar'     => $user->avatar,
                'address'    => $user->address,
                'status'     => $user->status,
                'role'       => $user->role,
                'biography'  => $user->biography,
            ],
        ];
    }

    /**
     * Resend verification OTP to user's email.
     *
     * @param  string  $email
     * @return array
     */
    public function resendOtp(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'User not found.',
            ];
        }

        if ($user->otp_verified_at) {
            return [
                'success' => false,
                'code'    => 409,
                'message' => 'Email already verified.',
            ];
        }

        $newOtp = rand(1000, 9999);
        $user->otp            = $newOtp;
        $user->otp_expires_at = Carbon::now()->addMinutes(60);
        $user->save();

        Mail::to($user->email)->queue(new OtpMail($newOtp, $user, 'Verify Your Email Address'));

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'A new OTP has been sent to your email address.',
            'otp'     => $newOtp,
        ];
    }

    /**
     * Helper: Generate Random Alphanumeric String
     */
    private function randomAlphaNum(int $length = 4): string
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
    }
}
