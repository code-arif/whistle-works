<?php

use \Illuminate\Support\Facades\Log;
use App\Helpers\Helper;
use App\Http\Middleware\ApiOtpVerifiedMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\WebAdminMiddleware;
use App\Http\Middleware\WebAuthCheckMiddleware;
use App\Http\Middleware\WebOtpVerifiedMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;



return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'web-admin'])->prefix('admin')->name('admin.')->group(base_path('routes/web-admin.php'));
            Route::middleware(['web', 'web-admin', HandleInertiaRequests::class])->prefix('admin/v2')->name('admin.v2.')->group(base_path('routes/web-admin-v2.php'));
        }
    )
    ->withBroadcasting(
        __DIR__ . '/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['auth:api']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'web-admin'             => WebAdminMiddleware::class,
            'api-otp'               => ApiOtpVerifiedMiddleware::class,
            'web-otp'               => WebOtpVerifiedMiddleware::class,
            'check'                 => WebAuthCheckMiddleware::class,
            'role'                  => RoleMiddleware::class,
            'permission'            => PermissionMiddleware::class,
            'role_or_permission'    => RoleOrPermissionMiddleware::class
        ]);
        $middleware->validateCsrfTokens(except: [
            'webhook/stripe',
            'webhook/stripe/*',
            'api/webhook/stripe',
            'api/webhook/stripe/*',
            'https://whistle-works.netlify.app/*',
            'http://localhost:5173',
            'http://localhost:5173/*',
            'https://admin.whistleworks.org',
            'https://admin.whistleworks.org/*',
            'https://admin.whistleworks.org/api/',
            'https://admin.whistleworks.org/api/*'
        ]);
        $middleware->api([
            StartSession::class,
        ]);
    })

    // ->withSchedule(function (Schedule $schedule) {
    //     // $schedule->command('app:send-emails')->everySecond();
    //     $schedule->command('notifications:send-special-date')->daily();
    //     $schedule->command('app:partnertrashdelete')->daily();
    // })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                Log::info('[API Error Debug Log]', [
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                    'url'       => $request->fullUrl(),
                    'method'    => $request->method(),
                    'user_id'   => auth('api')->id(),
                    'user_email'=> auth('api')->user()?->email,
                    'user_roles'=> auth('api')->user()?->getRoleNames()->toArray(),
                    'user_roles_db' => auth('api')->user()?->roles->toArray(),
                ]);

                if ($e instanceof ValidationException) {
                    return Helper::jsonErrorResponse($e->getMessage(), 422, $e->errors());
                }

                if ($e instanceof ModelNotFoundException) {
                    return Helper::jsonErrorResponse($e->getMessage(), 404);
                }

                if ($e instanceof AuthenticationException) {
                    return Helper::jsonErrorResponse($e->getMessage(), 401);
                }
                if ($e instanceof AuthorizationException) {
                    return Helper::jsonErrorResponse($e->getMessage(), 403);
                }
                // Dynamically determine the status code if available
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

                return Helper::jsonErrorResponse($e->getMessage(), $statusCode);
            } else {
                return null;
            }
        });
    })->create();
