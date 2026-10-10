<?php

namespace App\Http\Middleware;

use App\Enums\SalonRole;
use App\Services\CurrentSalon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnsureSalonUsersManagement
{
    public function __construct(private readonly CurrentSalon $context) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        $allowed = $user?->isSuperAdmin() || $user?->salons()
            ->whereKey($this->context->get()->id)
            ->wherePivot('role', SalonRole::Owner->value)
            ->wherePivot('is_active', true)
            ->exists();

        if (! $allowed) {
            return new JsonResponse(['message' => 'فقط مالک سالن اجازه مدیریت کاربران را دارد.'], 403);
        }

        return $next($request);
    }
}
