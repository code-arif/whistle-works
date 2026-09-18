<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\WebCustomRedirectMiddleware;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        if ($request->user()->hasVerifiedEmail()) {
            return app(WebCustomRedirectMiddleware::class)->handle($request, function () {});
        }

        return Inertia::render('Auth/VerifyEmail', [
            'status' => session('status'),
        ]);
    }
}

