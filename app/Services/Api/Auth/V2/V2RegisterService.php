<?php

namespace App\Services\Api\Auth\V2;

use App\Mail\AdminRegistrationMail;
use App\Mail\V2\EmailVerificationMail;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class V2RegisterService
{
    /**
     * Register a new user with email verification token.
     *
     * @param  array  $validated
     * @return array
     * @throws Exception
     */
    public function register(array $validated): array
    {
        DB::beginTransaction();

        try {
            $existingUser = User::where('email', strtolower($validated['email']))->first();

            if ($existingUser) {
                if (!$existingUser->otp_verified_at) {
                    $tokenExpired = $existingUser->email_verification_token_expires_at
                        ? Carbon::parse($existingUser->email_verification_token_expires_at)->isPast()
                        : true;

                    if ($tokenExpired) {
                        $existingUser->forceDelete();
                        Log::info('Deleted expired unverified user: ' . $existingUser->email);
                    } else {
                        DB::commit();
                        return $this->resendVerificationLink($existingUser);
                    }
                } else {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'code'    => 409,
                        'message' => 'Email address is already registered and verified.',
                    ];
                }
            }

            $existingPhone = User::where('phone', $validated['phone'])
                ->whereNotNull('otp_verified_at')
                ->first();

            if ($existingPhone) {
                DB::rollBack();
                return [
                    'success' => false,
                    'code'    => 409,
                    'message' => 'Phone number is already registered.',
                ];
            }

            do {
                $slug = $validated['first_name'] . rand(1000000000, 9999999999);
            } while (User::where('slug', $slug)->exists());

            $username          = '@' . strtolower($validated['first_name']) . '_' . $this->randomAlphaNum(4);
            $verificationToken = Str::random(64);
            $tokenExpiry       = Carbon::now()->addHours(config('auth.email_verification_token_expiry', 24));

            $user = User::create([
                'first_name'                          => $validated['first_name'],
                'last_name'                           => $validated['last_name'],
                'address'                             => $validated['address'],
                'phone'                               => $validated['phone'],
                'username'                            => $username,
                'slug'                                => $slug,
                'email'                               => strtolower($validated['email']),
                'password'                            => Hash::make($validated['password']),
                'email_verification_token'            => hash('sha256', $verificationToken),
                'email_verification_token_expires_at' => $tokenExpiry,
                'status'                              => 'active',
                'last_activity_at'                    => now(),
                'biography'                           => $validated['biography'] ?? null,
            ]);

            DB::table('model_has_roles')->insert([
                'role_id'    => $validated['role'],
                'model_type' => 'App\Models\User',
                'model_id'   => $user->id,
            ]);

            $verificationUrl = $this->generateVerificationUrl($verificationToken, $user->email);
            // Mail::to($user->email)->queue(new EmailVerificationMail($user, $verificationUrl));

            DB::commit();

            Log::info('User registered successfully: ' . $user->email);

            return [
                'success' => true,
                'code'    => 201,
                'message' => 'Registration successful! Please check your email to verify your account. The verification link will expire in 24 hours.',
                'data'    => [
                    'email'            => $user->email,
                    'expires_at'       => $tokenExpiry->toDateTimeString(),
                    'verification_url' => $verificationUrl,
                ],
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Verify email with token.
     *
     * @param  string  $token
     * @param  string  $email
     * @return array
     */
    public function verifyEmail(string $token, string $email): array
    {
        $hashedToken = hash('sha256', $token);

        $user = User::where('email', $email)
            ->where('email_verification_token', $hashedToken)
            ->first();

        if (!$user) {
            return [
                'status'  => 'error',
                'message' => 'Invalid or already used verification link.',
            ];
        }

        if ($user->otp_verified_at) {
            return [
                'status'  => 'info',
                'message' => 'Email already verified. Please login.',
            ];
        }

        if (
            !$user->email_verification_token_expires_at ||
            Carbon::parse($user->email_verification_token_expires_at)->isPast()
        ) {
            $user->forceDelete();
            return [
                'status'  => 'error',
                'message' => 'Verification link expired. Please register again.',
            ];
        }

        $user->update([
            'otp_verified_at'                     => now(),
            'email_verification_token'            => null,
            'email_verification_token_expires_at' => null,
        ]);

        $this->notifyAdmins($user);

        $jwtToken = auth('api')->login($user);

        return [
            'status'  => 'success',
            'message' => 'Email verified successfully!',
            'data'    => [
                'token'      => $jwtToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user_id'    => $user->id,
            ],
        ];
    }

    /**
     * Resend verification email.
     *
     * @param  string  $email
     * @return array
     */
    public function resendVerification(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'code'    => 404,
                'message' => 'User not found with this email address.',
            ];
        }

        if ($user->otp_verified_at) {
            return [
                'success' => false,
                'code'    => 409,
                'message' => 'Email already verified. Please login.',
            ];
        }

        return $this->resendVerificationLink($user);
    }

    /**
     * Helper: Generate verification token and dispatch email.
     */
    public function resendVerificationLink(User $user): array
    {
        $verificationToken = Str::random(64);
        $tokenExpiry       = Carbon::now()->addHours(config('auth.email_verification_token_expiry', 24));

        $user->update([
            'email_verification_token'            => hash('sha256', $verificationToken),
            'email_verification_token_expires_at' => $tokenExpiry,
        ]);

        $verificationUrl = $this->generateVerificationUrl($verificationToken, $user->email);
        // Mail::to($user->email)->queue(new EmailVerificationMail($user, $verificationUrl));

        return [
            'success' => true,
            'code'    => 200,
            'message' => 'A verification email has been sent to your email address. Please check your inbox.',
            'data'    => [
                'email'      => $user->email,
                'expires_at' => $tokenExpiry->toDateTimeString(),
            ],
        ];
    }

    /**
     * Helper: Generate Verification URL
     */
    public function generateVerificationUrl(string $token, string $email): string
    {
        $apiUrl = config('app.url');
        return "{$apiUrl}/verify-email?token={$token}&email=" . urlencode($email);
    }

    /**
     * Helper: Notify system admins upon verification
     */
    private function notifyAdmins(User $user): void
    {
        try {
            $notiData = [
                'user_id'  => $user->id,
                'title'    => 'New user registered successfully.',
                'body'     => $user->first_name . ' ' . $user->last_name . ' has registered and verified their email.',
                'username' => $user->username,
                'email'    => $user->email,
                'phone'    => $user->phone,
                'role'     => $user->role ?? 'user',
                'status'   => $user->status,
            ];

            Mail::to('drewbontrager@gmail.com')->queue(new AdminRegistrationMail($notiData));
        } catch (Exception $e) {
            Log::error('Admin notification failed: ' . $e->getMessage());
        }
    }

    private function randomAlphaNum(int $length = 4): string
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
    }
}
