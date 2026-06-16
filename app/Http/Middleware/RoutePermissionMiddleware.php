<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoutePermissionMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        // موقتاً همه کاربران عبور کنند تا نرم‌افزار بالا بیاید.
        // بعداً منطق واقعی دسترسی اینجا فعال می‌شود و این bypass حذف می‌شود.
        return $next($request);
    }
}
