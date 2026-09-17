<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'admin-v2';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id'    => $request->user()->id,
                    'name'  => trim(($request->user()->first_name ?? '') . ' ' . ($request->user()->last_name ?? '')) ?: ($request->user()->name ?? 'Administrator'),
                    'email' => $request->user()->email,
                    'role'  => $request->user()->roles->pluck('name')->first() ?? 'Admin',
                    'avatar'=> $request->user()->avatar ? (str_starts_with($request->user()->avatar, 'http') ? $request->user()->avatar : asset($request->user()->avatar)) : null,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('t-success') ?? $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('t-error') ?? $request->session()->get('error'),
            ],
            'settings' => [
                'app_name' => config('app.name', 'Whistle-Works'),
                'site_title' => function_exists('settings') ? (settings()->title ?? 'Whistle-Works') : 'Whistle-Works',
                'logo' => ($logo = function_exists('settings') ? (settings()->logo ?? null) : null)
                    ? ((str_starts_with($logo, 'http') || str_starts_with($logo, 'data:')) ? $logo : asset($logo))
                    : null,
            ],
        ]);
    }
}
