<?php

use App\Services\Appointments\ExpirePastAppointments;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(ExpirePastAppointments::class)->handle())
    ->name('appointments:expire-past')
    ->everyMinute()
    ->withoutOverlapping();
