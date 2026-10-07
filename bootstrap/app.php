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
        // APP_URL's host (and its subdomains), plus any extra names the site answers to, e.g. an apex domain or a
        // custom domain not yet in APP_URL: TRUSTED_HOSTS=example.com. Render's own hostname is always trusted.
        $middleware->trustHosts(at: fn () => array_map(fn (string $host) => '^(.+\.)?'.preg_quote($host).'$', config('app.trusted_hosts')));

        // Mail clients' one-click unsubscribe POSTs without a session. The link is signed instead.
        $middleware->validateCsrfTokens(except: ['alerts/unsubscribe/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
