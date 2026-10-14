<?php

use App\Http\Middleware\EnsureAppointmentSettingsAccess;
use App\Http\Middleware\EnsurePanelAccess;
use App\Http\Middleware\EnsureSalonUsersManagement;
use App\Http\Middleware\EnsureServiceAccess;
use App\Http\Middleware\EnsureServiceCategoryAccess;
use App\Http\Middleware\ResolveSalonContext;
use App\Services\Errors\DatabaseExceptionReporter;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->alias([
            'panel' => EnsurePanelAccess::class,
            'salon' => ResolveSalonContext::class,
            'manage-salon-users' => EnsureSalonUsersManagement::class,
            'service-categories' => EnsureServiceCategoryAccess::class,
            'services' => EnsureServiceAccess::class,
            'appointment-settings' => EnsureAppointmentSettingsAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $exception): void {
            app(DatabaseExceptionReporter::class)->report($exception);
        });
    })->create();
