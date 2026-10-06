<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebCustomRedirectMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();

            if ($user->hasRole('admin') && $user->status === 'active') {
                return redirect()->intended(route('admin.v2.dashboard', absolute: false));
            }

            Auth::guard('web')->logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Access denied. Only active administrators can access this portal.',
            ]);
        }

        return redirect()->route('login');
    }
}