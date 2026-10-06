<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->guest(route('login'));
        }

        $user = Auth::guard('web')->user();

        if ($user->hasRole('admin') && $user->status === 'active') {
            return $next($request);
        }

        Auth::guard('web')->logout();
        abort(403, 'Access denied. Administrator privileges required.');
    }
}

