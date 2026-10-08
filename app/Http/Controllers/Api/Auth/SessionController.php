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
}
