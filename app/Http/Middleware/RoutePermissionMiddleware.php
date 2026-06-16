<?php

namespace App\Http\Middleware;

class RoutePermissionMiddleware extends EnforceRoutePermission
{
    // Dedicated alias target for routes that use the `route.permission` middleware.
    // Permission enforcement stays in EnforceRoutePermission so the existing
    // route-to-permission mapping and authorization behavior remain unchanged.
}
