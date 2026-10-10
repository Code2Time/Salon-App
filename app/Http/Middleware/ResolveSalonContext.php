<?php

namespace App\Http\Middleware;

use App\Models\Salon;
use App\Services\CurrentSalon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResolveSalonContext
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        $requestedId = $request->header('X-Salon-ID');

        if ($user->isSuperAdmin()) {
            $query = Salon::query()->where('is_active', true);
        } else {
            $query = $user->salons()->wherePivot('is_active', true)->where('salons.is_active', true);
        }

        $salon = $requestedId ? $query->where('salons.id', $requestedId)->first() : $query->first();
        if (! $salon) {
            return new JsonResponse(['message' => 'دسترسی به سالن انتخاب‌شده مجاز نیست.'], 403);
        }

        app(CurrentSalon::class)->set($salon);

        return $next($request);
    }
}
