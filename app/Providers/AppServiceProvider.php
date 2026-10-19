<?php

namespace App\Providers;

use App\Services\CurrentSalon;
use App\Services\Otp\FixedOtpSender;
use App\Services\Otp\OtpSender;
use App\Services\Otp\SmsIrOtpSender;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OtpSender::class, function () {
            return match (config('otp.driver')) {
                'fixed' => new FixedOtpSender,
                'smsir_sandbox', 'smsir' => new SmsIrOtpSender,
                default => throw new \LogicException('OTP provider is not configured.'),
            };
        });
        $this->app->scoped(CurrentSalon::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
