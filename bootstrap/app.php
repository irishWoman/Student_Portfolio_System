<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // "role" guards whole route groups (student / faculty / chair / admin).
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // The app has no 'home' or 'dashboard' route, so the framework's
        // default guest-middleware redirect falls all the way through to
        // '/' -- which is itself guest-only, so an authenticated visitor to
        // '/' or '/login' bounces forever. Send them to their own landing
        // page instead.
        $middleware->redirectUsersTo(
            fn ($request) => $request->user() ? route($request->user()->homeRoute()) : route('login')
        );

        // Railway (and every other PaaS: Render, Heroku, Fly...) terminates
        // HTTPS at its own edge and forwards plain HTTP to the container.
        // Without this, Laravel never sees the request as secure, so every
        // asset() / route() / Vite URL it generates comes out as http://,
        // which the browser then blocks as mixed content on an https:// page.
        // The app has no direct public exposure other than through that
        // edge, so trusting all proxies here is safe.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
