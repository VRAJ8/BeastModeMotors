<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind a load balancer (Render, Railway, Fly…) so URLs are generated with https.
        $middleware->trustProxies(at: '*');

        // ...but only answer to our own host, so a forged Host header can't poison links in emails.
        $middleware->trustHosts();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
