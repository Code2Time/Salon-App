<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnsurePanelAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->user()?->canAccessPanel()) {
            return new JsonResponse(['message' => 'شما اجازه دسترسی به پنل مدیریت را ندارید.'], 403);
        }

        return $next($request);
    }
}
