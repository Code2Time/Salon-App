<?php

use App\Http\Controllers\Api\Auth\OtpAuthController;
use App\Http\Controllers\Api\Auth\SessionController;
use App\Http\Controllers\Api\CustomerAppointmentController;
use App\Http\Controllers\Api\CustomerServiceController;
use App\Http\Controllers\Api\Panel\AppointmentController;
use App\Http\Controllers\Api\Panel\AppointmentScheduleController;
use App\Http\Controllers\Api\Panel\DashboardController;
use App\Http\Controllers\Api\Panel\EmployeeScheduleController;
use App\Http\Controllers\Api\Panel\NotificationController;
use App\Http\Controllers\Api\Panel\ProfileController as PanelProfileController;
use App\Http\Controllers\Api\Panel\SalonUserController;
use App\Http\Controllers\Api\Panel\ServiceCategoryController;
use App\Http\Controllers\Api\Panel\ServiceController;
use App\Http\Controllers\Api\ProfileController as CustomerProfileController;
use App\Services\CurrentSalon;
use Illuminate\Support\Facades\Route;

Route::get('/catalog/services', [CustomerServiceController::class, 'index']);
Route::get('/catalog/service-categories', [CustomerServiceController::class, 'categories']);

Route::prefix('auth')->group(function () {
    Route::post('/otp/request', [OtpAuthController::class, 'request'])->middleware('throttle:5,1');
    Route::post('/otp/verify', [OtpAuthController::class, 'verify'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [SessionController::class, 'user']);
        Route::post('/onboarding/complete', [SessionController::class, 'completeOnboarding']);
        Route::put('/onboarding/test-state', [SessionController::class, 'updateOnboardingForTesting']);
        Route::post('/logout', [SessionController::class, 'destroy']);
    });
});

Route::post('/profile', [CustomerProfileController::class, 'update'])
    ->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('/appointments')->group(function () {
    Route::post('/availability', [CustomerAppointmentController::class, 'availability']);
    Route::get('/', [CustomerAppointmentController::class, 'index']);
    Route::post('/', [CustomerAppointmentController::class, 'store']);
    Route::get('/{appointment}', [CustomerAppointmentController::class, 'show']);
    Route::post('/{appointment}/cancel', [CustomerAppointmentController::class, 'cancel']);
});

Route::get('/panel/context', fn (CurrentSalon $context) => response()->json([
    'data' => ['salon_id' => $context->get()->id, 'salon_name' => $context->get()->name],
]))->middleware(['auth:sanctum', 'panel', 'salon']);

Route::get('/panel/dashboard', DashboardController::class)
    ->middleware(['auth:sanctum', 'panel', 'salon', 'dashboard']);

Route::apiResource('/panel/users', SalonUserController::class)
    ->middleware(['auth:sanctum', 'panel', 'salon', 'manage-salon-users']);

Route::post('/panel/profile', [PanelProfileController::class, 'update'])
    ->middleware(['auth:sanctum', 'panel']);

Route::middleware(['auth:sanctum', 'panel', 'salon'])->prefix('/panel/service-categories')->group(function () {
    Route::get('/', [ServiceCategoryController::class, 'index'])->middleware('service-categories');
    Route::get('/{service_category}', [ServiceCategoryController::class, 'show'])->middleware('service-categories');
    Route::get('/{service_category}/deletion-impact', [ServiceCategoryController::class, 'deletionImpact'])
        ->middleware('service-categories:manage');
    Route::post('/', [ServiceCategoryController::class, 'store'])->middleware('service-categories:manage');
    Route::post('/{service_category}', [ServiceCategoryController::class, 'update'])->middleware('service-categories:manage');
    Route::delete('/{service_category}', [ServiceCategoryController::class, 'destroy'])->middleware('service-categories:manage');
});

Route::middleware(['auth:sanctum', 'panel', 'salon'])->prefix('/panel/services')->group(function () {
    Route::get('/', [ServiceController::class, 'index'])->middleware('services');
    Route::get('/options', [ServiceController::class, 'options'])->middleware('services');
    Route::get('/{service}', [ServiceController::class, 'show'])->middleware('services');
    Route::post('/', [ServiceController::class, 'store'])->middleware('services:manage');
    Route::put('/{service}', [ServiceController::class, 'update'])->middleware('services:manage');
    Route::delete('/{service}', [ServiceController::class, 'destroy'])->middleware('services:manage');
});

Route::middleware(['auth:sanctum', 'panel', 'salon', 'appointments'])->prefix('/panel/appointments')->group(function () {
    Route::get('/', [AppointmentController::class, 'index']);
    Route::get('/options', [AppointmentController::class, 'options']);
    Route::post('/availability', [AppointmentController::class, 'availability']);
    Route::post('/', [AppointmentController::class, 'store']);
    Route::get('/{appointment}', [AppointmentController::class, 'show']);
    Route::put('/{appointment}/status', [AppointmentController::class, 'status']);
    Route::put('/{appointment}/reschedule', [AppointmentController::class, 'reschedule']);
    Route::delete('/{appointment}', [AppointmentController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'panel', 'salon', 'appointment-settings'])
    ->prefix('/panel/appointment-schedule')->group(function () {
        Route::get('/', [AppointmentScheduleController::class, 'show']);
        Route::put('/settings', [AppointmentScheduleController::class, 'updateSettings']);
        Route::put('/days/{date}', [AppointmentScheduleController::class, 'updateDay']);
    });

Route::middleware(['auth:sanctum', 'panel', 'salon'])->prefix('/panel/employee-schedule')->group(function () {
    Route::get('/', [EmployeeScheduleController::class, 'show']);
    Route::get('/employees', [EmployeeScheduleController::class, 'employees']);
    Route::put('/days/{date}', [EmployeeScheduleController::class, 'update']);
});
Route::middleware('auth:sanctum')->prefix('/notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('/read-all', [NotificationController::class, 'readAll']);
    Route::post('/{notification}/read', [NotificationController::class, 'read']);
});
