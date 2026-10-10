<?php

use App\Http\Controllers\Api\Auth\OtpAuthController;
use App\Http\Controllers\Api\Auth\SessionController;
use App\Http\Controllers\Api\Panel\ProfileController;
use App\Http\Controllers\Api\Panel\SalonUserController;
use App\Services\CurrentSalon;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/otp/request', [OtpAuthController::class, 'request'])->middleware('throttle:5,1');
    Route::post('/otp/verify', [OtpAuthController::class, 'verify'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [SessionController::class, 'user']);
        Route::post('/logout', [SessionController::class, 'destroy']);
    });
});

Route::get('/panel/context', fn (CurrentSalon $context) => response()->json([
    'data' => ['salon_id' => $context->get()->id, 'salon_name' => $context->get()->name],
]))->middleware(['auth:sanctum', 'panel', 'salon']);

Route::apiResource('/panel/users', SalonUserController::class)
    ->middleware(['auth:sanctum', 'panel', 'salon', 'manage-salon-users']);

Route::post('/panel/profile', [ProfileController::class, 'update'])
    ->middleware(['auth:sanctum', 'panel']);
