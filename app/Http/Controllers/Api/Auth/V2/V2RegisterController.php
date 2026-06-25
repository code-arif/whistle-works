<?php

namespace App\Http\Controllers\Api\Auth\V2;

use App\Events\RegistrationNotificationEvent;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Mail\AdminRegistrationMail;
use App\Mail\V2\EmailVerificationMail;
use App\Models\User;
use App\Notifications\RegistrationNotification;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class V2RegisterController extends Controller
{
    use ApiResponse;

    public $select;

    public function __construct()
    {
        parent::__construct();
        $this->select = ['id', 'first_name', 'last_name', 'username', 'email', 'avatar', 'otp_verified_at', 'last_activity_at'];
    }

    /**
     * User Registration with Email Verification Token
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|string|email|max:150|unique:users,email',
            'phone'      => 'required|string|max:150|unique:users,phone',
            'address'    => 'required|string',
            'password'   => 'required|string|min:6|confirmed',
            'agree'      => 'required|in:true',
            'role'       => 'required',
            'biography'  => 'nullable|string|max:2500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // validated data
        $validated = $validator->validated();

        try {
            DB::beginTransaction();

            // Check if email exists
            $existingUser = User::where('email', strtolower($validated['email']))->first();

            // Handle unverified existing user
            if ($existingUser) {
                // If user exists but email not verified and token expired
                if (!$existingUser->otp_verified_at) {
                    $tokenExpired = $existingUser->email_verification_token_expires_at
                        ? Carbon::parse($existingUser->email_verification_token_expires_at)->isPast()
                        : true;

                    if ($tokenExpired) {
                        // Delete old unverified user and allow new registration
                        $existingUser->forceDelete();
                        Log::info('Deleted expired unverified user: ' . $existingUser->email);
                    } else {
                        // Token still valid, resend verification email
                        DB::commit(); // Commit before returning
                        return $this->resendVerificationLink($existingUser);
                    }
                } else {
                    // User verified, email already taken
                    DB::rollBack();
                    return $this->error(null, 'Email address is already registered and verified.', 409);
                }
            }

            // Check if phone exists and is verified
            $existingPhone = User::where('phone', $validated['phone'])
                ->whereNotNull('otp_verified_at')
                ->first();

            if ($existingPhone) {
                DB::rollBack();
                return $this->error(null, 'Phone number is already registered.', 409);
            }

            // Generate unique slug and username
            do {
                $slug = $validated['first_name'] . rand(1000000000, 9999999999);
            } while (User::where('slug', $slug)->exists());

            $username = '@' . strtolower($validated['first_name']) . '_' . $this->randomAlphaNum(4);

            // Generate email verification token
            $verificationToken = Str::random(64);
            $tokenExpiry = Carbon::now()->addHours(config('auth.email_verification_token_expiry', 24));

            // Create user
            $user = User::create([
                'first_name'   => $validated['first_name'],
                'last_name'    => $validated['last_name'],
                'address'      => $validated['address'],
                'phone'        => $validated['phone'],
                'username'     => $username,
                'slug'         => $slug,
                'email'        => strtolower($validated['email']),
                'password'     => Hash::make($validated['password']),
                'email_verification_token'             => hash('sha256', $verificationToken),
                'email_verification_token_expires_at'  => $tokenExpiry,
                'status'       => 'active',
                'last_activity_at' => now(),
                'biography'    => $validated['biography'] ?? null,
            ]);

            // Assign role
            DB::table('model_has_roles')->insert([
                'role_id'    => $validated['role'],
                'model_type' => 'App\Models\User',
                'model_id'   => $user->id
            ]);

            // Send verification email
            $verificationUrl = $this->generateVerificationUrl($verificationToken, $user->email);
            // Mail::to($user->email)->queue(new EmailVerificationMail($user, $verificationUrl));

            DB::commit();

            Log::info('User registered successfully: ' . $user->email);

            return $this->success(
                'Registration successful! Please check your email to verify your account. The verification link will expire in 24 hours.',
                [
                    'email'      => $user->email,
                    'expires_at' => $tokenExpiry->toDateTimeString(),
                    'verification_url' => $verificationUrl, // For testing purposes, remove in production
                ],
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Registration failed: ' . $e->getMessage());
            return $this->error(['error' => $e->getMessage()], 'User registration failed', 500);
        }
    }

    public function verifyEmail(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');

        if (!$token || !$email) {
            return $this->redirectToFrontend(
                'error',
                'Invalid verification link.'
            );
        }

        $hashedToken = hash('sha256', $token);

        $user = User::where('email', $email)
            ->where('email_verification_token', $hashedToken)
            ->first();

        if (!$user) {
            return $this->redirectToFrontend(
                'error',
                'Invalid or already used verification link.'
            );
        }

        if ($user->otp_verified_at) {
            return $this->redirectToFrontend(
                'info',
                'Email already verified. Please login.'
            );
        }

        if (
            !$user->email_verification_token_expires_at ||
            Carbon::parse($user->email_verification_token_expires_at)->isPast()
        ) {
            $user->forceDelete();

            return $this->redirectToFrontend(
                'error',
                'Verification link expired. Please register again.'
            );
        }

        // ✅ Verify user
        $user->update([
            'otp_verified_at' => now(),
            'email_verification_token' => null,
            'email_verification_token_expires_at' => null,
        ]);

        // Notify admins
        $this->notifyAdmins($user);

        // Auto login (JWT)
        $token = auth('api')->login($user);

        return $this->redirectToFrontend(
            'success',
            'Email verified successfully!',
            [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user_id' => $user->id,
            ]
        );
    }



    /**
     * Verify Email with Token (GET request from email link)
     */
    // public function verifyEmail(Request $request)
    // {
    //     // Validate token and email from query parameters
    //     $validator = Validator::make($request->all(), [
    //         'token' => 'required|string',
    //         'email' => 'required|email',
    //     ]);

    //     if ($validator->fails()) {
    //         Log::warning('Email verification validation failed', [
    //             'errors' => $validator->errors(),
    //             'request' => $request->all()
    //         ]);
    //         return $this->redirectToFrontend('error', 'Invalid verification link.');
    //     }

    //     try {
    //         // Find user by email
    //         $user = User::where('email', $request->input('email'))->first();

    //         if (!$user) {
    //             Log::warning('Verification attempt for non-existent user', [
    //                 'email' => $request->input('email')
    //             ]);
    //             return $this->redirectToFrontend('error', 'User not found. Please register again.');
    //         }

    //         // Check if already verified
    //         if ($user->otp_verified_at) {
    //             Log::info('User already verified', ['email' => $user->email]);
    //             return $this->redirectToFrontend('info', 'Email already verified. Please login.', [
    //                 'email' => $user->email,
    //                 'redirect' => 'login'
    //             ]);
    //         }

    //         // Check if user has a verification token
    //         if (!$user->email_verification_token) {
    //             Log::warning('No verification token found', ['email' => $user->email]);
    //             return $this->redirectToFrontend('error', 'Invalid verification link. Please request a new one.');
    //         }

    //         // Hash the token from request and compare
    //         $hashedToken = hash('sha256', $request->input('token'));

    //         if ($user->email_verification_token !== $hashedToken) {
    //             Log::warning('Invalid verification token', [
    //                 'email' => $user->email,
    //                 'provided_hash' => substr($hashedToken, 0, 10) . '...',
    //                 'stored_hash' => substr($user->email_verification_token, 0, 10) . '...'
    //             ]);
    //             return $this->redirectToFrontend('error', 'Invalid verification token. Please request a new one.');
    //         }

    //         // Check if token expired
    //         if (!$user->email_verification_token_expires_at || Carbon::parse($user->email_verification_token_expires_at)->isPast()) {
    //             Log::warning('Verification token expired', [
    //                 'email' => $user->email,
    //                 'expired_at' => $user->email_verification_token_expires_at
    //             ]);

    //             // Delete expired unverified user
    //             $user->forceDelete();

    //             return $this->redirectToFrontend('error', 'Verification link has expired. Please register again.');
    //         }

    //         // Update verification status
    //         $user->update([
    //             'otp_verified_at'                      => now(),
    //             'email_verification_token'             => null,
    //             'email_verification_token_expires_at'  => null,
    //         ]);

    //         // Notify admins about new verified user
    //         $this->notifyAdmins($user);

    //         Log::info('Email verified successfully', ['email' => $user->email, 'user_id' => $user->id]);

    //         // Generate JWT token for auto-login
    //         $token = auth('api')->login($user);
    //         $expiresIn = auth('api')->factory()->getTTL() * 60;

    //         return $this->redirectToFrontend('success', 'Email verified successfully!', [
    //             'token'      => $token,
    //             'token_type' => 'bearer',
    //             'expires_in' => $expiresIn,
    //             'user_id'    => $user->id,
    //             'email'      => $user->email,
    //             'username'   => $user->username,
    //             'first_name' => $user->first_name,
    //             'last_name'  => $user->last_name,
    //         ]);
    //     } catch (Exception $e) {
    //         Log::error('Email verification failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         return $this->redirectToFrontend('error', 'Verification failed. Please try again or contact support.');
    //     }
    // }

    /**
     * Resend Verification Email
     */
    public function resendVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::where('email', $request->input('email'))->first();

            if (!$user) {
                return $this->error(null, 'User not found with this email address.', 404);
            }

            if ($user->otp_verified_at) {
                return $this->error(null, 'Email already verified. Please login.', 409);
            }

            return $this->resendVerificationLink($user);
        } catch (Exception $e) {
            Log::error('Resend verification failed: ' . $e->getMessage());
            return $this->error(null, 'Failed to resend verification email.', 500);
        }
    }

    /**
     * Helper: Resend Verification Link
     */
    private function resendVerificationLink($user)
    {
        // Generate new token
        $verificationToken = Str::random(64);
        $tokenExpiry = Carbon::now()->addHours(config('auth.email_verification_token_expiry', 24));

        $user->update([
            'email_verification_token'             => hash('sha256', $verificationToken),
            'email_verification_token_expires_at'  => $tokenExpiry,
        ]);

        // Send verification email
        $verificationUrl = $this->generateVerificationUrl($verificationToken, $user->email);
        Mail::to($user->email)->queue(new EmailVerificationMail($user, $verificationUrl));

        Log::info('Verification email resent: ' . $user->email);

        return $this->success(
            'A verification email has been sent to your email address. Please check your inbox.',
            [
                'email'      => $user->email,
                'expires_at' => $tokenExpiry->toDateTimeString(),
            ],
            200
        );
    }

    /**
     * Helper: Generate Verification URL
     * IMPORTANT: This should point to the BACKEND API endpoint, not frontend
     */
    private function generateVerificationUrl($token, $email)
    {
        $apiUrl = config('app.url'); // api.whistleworks.org
        return "{$apiUrl}/verify-email?token={$token}&email=" . urlencode($email);
    }


    /**
     * Helper: Redirect to Frontend with Query Parameters
     */
    private function redirectToFrontend($status, $message, $data = [])
    {
        $frontendBase = rtrim(config('app.frontend_url'), '/');

        // frontend callback route
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

        // important
        return redirect()->away($redirectUrl);
    }


    private function notifyAdmins($user)
    {
        try {
            $notiData = [
                'user_id' => $user->id,
                'title'   => 'New user registered successfully.',
                'body'    => $user->first_name . ' ' . $user->last_name . ' has registered and verified their email.',
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role ?? 'user', // Depending on relation or field
                'status' => $user->status,
            ];

            Mail::to('drewbontrager@gmail.com')->queue(new AdminRegistrationMail($notiData));

            Log::info('Admin notifications sent', ['user_id' => $user->id]);
        } catch (Exception $e) {
            Log::error('Admin notification failed: ' . $e->getMessage());
            // Don't fail the verification if admin notification fails
        }
    }

    /**
     * Helper: Generate Random Alpha Numeric
     */
    private function randomAlphaNum($length = 4)
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
    }
}
