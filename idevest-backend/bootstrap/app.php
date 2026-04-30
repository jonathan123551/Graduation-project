<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Convert "" -> null and trim string inputs so that the typical frontend
        // habit of sending empty strings for unfilled optional fields does not
        // blow up `nullable|integer` / `nullable|numeric` validators with a 500.
        $middleware->convertEmptyStringsToNull();
        $middleware->trimStrings();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();