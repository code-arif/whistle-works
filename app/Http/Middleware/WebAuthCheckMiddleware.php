<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebAuthCheckMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();

            if ($user->hasRole('admin') && $user->status === 'active') {
                return redirect()->route('admin.v2.dashboard');
            }

            Auth::guard('web')->logout();
        }

        return $next($request);
    }
}

