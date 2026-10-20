<?php

use App\Http\Middleware\EnsureAccountSessionCurrent;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        channels: __DIR__.'/../routes/channels.php',
        attributes: ['middleware' => ['web', 'auth']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [EnsureAccountSessionCurrent::class]);

        $middleware->alias([
            'is_admin' => IsAdmin::class,
        ]);

        $middleware->redirectUsersTo(
            fn (Request $request) => $request->user()->isAdmin() ? route('admin.bookings.index') : route('dashboard')
        );

        $middleware->use([
            TrustProxies::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
