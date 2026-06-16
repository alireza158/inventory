<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user && $user->hasPermission($permission)) {
            return $next($request);
        }

        $message = 'شما دسترسی لازم برای مشاهده این بخش را ندارید.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        $redirect = url()->previous() !== $request->fullUrl()
            ? redirect()->back()
            : redirect()->route('dashboard');

        return $redirect->with('error', $message);
    }
}
