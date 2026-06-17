<?php

namespace App\Http\Middleware;

use App\Support\PermissionCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoutePermissionMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $permission = $permissions[0] ?? null;

        if ($permission === null) {
            $routeName = $request->route()?->getName();
            $permission = $routeName ? (PermissionCatalog::routePermissions()[$routeName] ?? null) : null;
        }

        if ($permission === null) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && $this->userHasPermission($user, $permission)) {
            return $next($request);
        }

        $message = 'شما دسترسی لازم برای انجام این عملیات را ندارید.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        $redirect = url()->previous() !== $request->fullUrl()
            ? redirect()->back()
            : redirect()->route('dashboard');

        return $redirect->with('error', $message);
    }

    private function userHasPermission($user, string $permission): bool
    {
        if ($user->hasPermission($permission)) {
            return true;
        }

        foreach (PermissionCatalog::permissionAliases()[$permission] ?? [] as $alias) {
            if ($user->hasPermission($alias)) {
                return true;
            }
        }

        return false;
    }
}
