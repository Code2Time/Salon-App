<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurrentUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public function user(Request $request): CurrentUserResource
    {
        return new CurrentUserResource($request->user()->load([
            'salons' => fn ($query) => $query->wherePivot('is_active', true)->where('salons.is_active', true),
        ]));
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::forgetGuards();

        return response()->json(['message' => 'از حساب خارج شدید.']);
    }

    public function completeOnboarding(Request $request): CurrentUserResource
    {
        $request->user()->update(['is_first_login' => false]);

        return new CurrentUserResource($request->user()->load([
            'salons' => fn ($query) => $query->wherePivot('is_active', true)->where('salons.is_active', true),
        ]));
    }

    public function updateOnboardingForTesting(Request $request): CurrentUserResource
    {
        abort_unless(config('app.debug'), 404);
        $data = $request->validate(['is_first_login' => ['required', 'boolean']]);
        $request->user()->update(['is_first_login' => $data['is_first_login']]);

        return new CurrentUserResource($request->user()->load([
            'salons' => fn ($query) => $query->wherePivot('is_active', true)->where('salons.is_active', true),
        ]));
    }
}
