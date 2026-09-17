<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
            'canResetPassword' => Route::has('password.request'),
        ]);
    }

    /**
     * Handle an incoming authentication request with Rate Limiting & Brute-Force defense.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // 1. Ensure client is not rate limited (max 5 attempts, then 1-minute lockout)
        $request->ensureIsNotRateLimited();

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'))) {
            $user = Auth::user();

            if (!$user->hasRole('admin')) {
                Auth::logout();
                RateLimiter::hit($request->throttleKey());
                Log::warning('Non-admin login attempt blocked on admin portal', [
                    'email' => $request->email,
                    'ip'    => $request->ip(),
                ]);
                return back()->withErrors([
                    'email' => 'Access denied. Only administrators can log in to this portal.',
                ]);
            }

            if ($user->status !== 'active') {
                Auth::logout();
                RateLimiter::hit($request->throttleKey());
                Log::warning('Deactivated admin login attempt blocked', [
                    'email' => $request->email,
                    'ip'    => $request->ip(),
                ]);
                return back()->withErrors([
                    'email' => 'Your account is deactivated. Please contact support.',
                ]);
            }

            // Authentication succeeded: clear rate limiter attempts
            RateLimiter::clear($request->throttleKey());

            $request->session()->regenerate();
            session()->flash('t-success', 'Signed in successfully');
            return redirect()->intended(route('admin.v2.dashboard', absolute: false));

        } else {
            // Failed credentials: hit rate limiter and log audit trail
            RateLimiter::hit($request->throttleKey());
            Log::warning('Failed admin login attempt', [
                'email' => $request->email,
                'ip'    => $request->ip(),
            ]);

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        session()->put('t-success', 'Logout Successfully');

        return redirect('/');
    }
}
