<?php

namespace App\Http\Middleware;

use App\Enums\SalonRole;
use App\Models\SalonUser;
use App\Services\CurrentSalon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnsureDashboardAccess
{
    public function __construct(private readonly CurrentSalon $context) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user()?->isSuperAdmin()) {
            return $next($request);
        }

        $membership = SalonUser::query()
            ->where('salon_id', $this->context->get()->id)
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->first();

        $allowed = $membership && (
            $membership->role === SalonRole::Owner
            || ($membership->role === SalonRole::Employee
                && in_array('dashboard', $membership->permissionNames(), true))
        );

        return $allowed
            ? $next($request)
            : new JsonResponse(['message' => 'اجازه دسترسی به داشبورد را ندارید.'], 403);
    }
}
