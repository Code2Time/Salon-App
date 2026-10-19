<?php

namespace App\Http\Middleware;

use App\Enums\SalonRole;
use App\Services\CurrentSalon;
use Closure;
use Illuminate\Http\Request;

class EnsureProductAccess
{
    public function handle(Request $request, Closure $next, string $ability = 'view'): mixed
    {
        $user = $request->user();
        $membership = $user->salons()->whereKey(app(CurrentSalon::class)->get()->id)->wherePivot('is_active', true)->first();
        $manager = $user->isSuperAdmin() || $membership?->pivot->role === SalonRole::Owner;
        abort_unless($manager || ($ability === 'view' && in_array('products', $membership?->pivot->permissionNames() ?? [], true)), 403, 'دسترسی لازم برای مدیریت محصولات و سفارش‌ها را ندارید.');

        return $next($request);
    }
}
