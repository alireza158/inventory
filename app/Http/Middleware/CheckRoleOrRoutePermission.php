<?php

namespace App\Http\Middleware;

use App\Support\PermissionCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleOrRoutePermission
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $this->deny($request);
        }

        $routeName = $request->route()?->getName();
        $routePermission = $routeName ? (PermissionCatalog::routePermissions()[$routeName] ?? null) : null;

        if ($routePermission !== null && PermissionCatalog::userHasPermission($user, $routePermission)) {
            return $next($request);
        }

        if ($user->hasAnyRole(explode('|', $roles))) {
            return $next($request);
        }

        return $this->deny($request);
    }

    private function deny(Request $request): Response
    {
        $message = 'شما دسترسی لازم برای انجام این عملیات را ندارید.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        $redirect = url()->previous() !== $request->fullUrl()
            ? redirect()->back()
            : redirect()->route('dashboard');

        return $redirect->with('error', $message);
    }
}
