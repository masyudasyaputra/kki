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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\UseBuiltAssetsForRemoteClients::class);
        $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $request) => \App\Support\DashboardDestination::url($request->user()));
        $middleware->alias([
        'role' => \App\Http\Middleware\RoleManager::class,
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

    
