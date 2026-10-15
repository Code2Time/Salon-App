<?php

namespace App\Http\Middleware;

use App\Enums\SalonRole;
use App\Models\SalonUser;
use App\Services\CurrentSalon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnsureAppointmentAccess
{
    public function __construct(private readonly CurrentSalon $context) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user()?->isSuperAdmin()) {
            return $next($request);
        }
        $member = SalonUser::query()->where('salon_id', $this->context->get()->id)->where('user_id', $request->user()->id)->where('is_active', true)->first();
        $allowed = $member && ($member->role === SalonRole::Owner || ($member->role === SalonRole::Employee && in_array('appointments', $member->permissionNames(), true)));

        return $allowed ? $next($request) : new JsonResponse(['message' => 'اجازه دسترسی به نوبت‌ها را ندارید.'], 403);
    }
}
