<?php

namespace App\Providers;

use App\Services\CurrentSalon;
use App\Services\Otp\FixedOtpSender;
use App\Services\Otp\OtpSender;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OtpSender::class, function () {
            if (config('otp.driver') !== 'fixed') {
                throw new \LogicException('OTP provider is not configured.');
            }

            return new FixedOtpSender;
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
