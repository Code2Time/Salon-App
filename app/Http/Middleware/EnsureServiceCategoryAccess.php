<?php

namespace App\Http\Middleware;

use App\Enums\SalonRole;
use App\Services\CurrentSalon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnsureServiceCategoryAccess
{
    public function __construct(private readonly CurrentSalon $context) {}

    public function handle(Request $request, Closure $next, string $ability = 'view'): mixed
    {
        $user = $request->user();
        if ($user?->isSuperAdmin()) {
            return $next($request);
        }

        $membership = $user?->salons()
            ->whereKey($this->context->get()->id)
            ->wherePivot('is_active', true)
            ->first();
        $role = $membership?->pivot->role;
        $permissions = $membership?->pivot->permissionNames() ?? [];
        $isOwner = $role === SalonRole::Owner;
        $canManage = $isOwner || in_array('manage_service_categories', $permissions, true);
        $canView = $canManage || in_array('service_categories', $permissions, true);

        if (($ability === 'manage' && ! $canManage) || ($ability !== 'manage' && ! $canView)) {
            return new JsonResponse(['message' => 'دسترسی لازم برای این عملیات دسته‌بندی خدمات را ندارید.'], 403);
        }

        return $next($request);
    }
}
