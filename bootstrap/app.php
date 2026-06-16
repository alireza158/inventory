<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ConvertRialCurrencyInputs;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnforceRoutePermission;
use App\Http\Middleware\CheckRoleOrRoutePermission;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', ConvertRialCurrencyInputs::class);

        $middleware->alias([
            'role' => CheckRoleOrRoutePermission::class,
            'permission' => CheckPermission::class,
            'route.permission' => EnforceRoutePermission::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
