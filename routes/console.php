<?php

use App\Enums\SalonRole;
use App\Jobs\SendEmailNotification;
use App\Jobs\SendTelegramNotification;
use App\Models\EmailDelivery;
use App\Models\Salon;
use App\Models\TelegramDelivery;
use App\Services\Appointments\ExpirePastAppointments;
use App\Services\Notifications\NotificationEmailTransport;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(ExpirePastAppointments::class)->handle())
    ->name('appointments:expire-past')
    ->everyMinute()
    ->withoutOverlapping();

Artisan::command('telegram:connect {salon : Salon slug} {user : Application user ID} {chat : Numeric Telegram private chat ID} {--disconnect}', function () {
    $salon = Salon::where('slug', $this->argument('salon'))->where('is_active', true)->first();
    $owner = $salon?->users()->whereKey($this->argument('user'))->where('users.is_active', true)
        ->wherePivot('role', SalonRole::Owner->value)->wherePivot('is_active', true)->first();
    if (! $owner || ! preg_match('/^[1-9][0-9]{0,19}$/D', $this->argument('chat'))) {
        $this->error('An active salon owner and a positive numeric Telegram user ID are required.');

        return 1;
    }
    $salon->users()->updateExistingPivot($owner->id, ['telegram_chat_id' => $this->option('disconnect') ? null : $this->argument('chat')]);
    $this->info('Telegram recipient updated.');

    return 0;
})->purpose('Connect or disconnect a salon owner Telegram recipient');

Artisan::command('telegram:retry {delivery : Pending or failed delivery ID}', function () {
    if (! config('telegram.enabled') || ! config('telegram.bot_token')) {
        $this->error('Enable Telegram, configure the bot token first.');

        return 1;
    }
    $delivery = TelegramDelivery::whereKey($this->argument('delivery'))->whereIn('status', ['pending', 'failed'])->first();
    if (! $delivery) {
        $this->error('Pending or failed delivery not found.');

        return 1;
    }
    $delivery->update(['status' => 'pending', 'last_error_code' => null]);
    SendTelegramNotification::dispatch($delivery->id);
    $this->info('Delivery queued.');

    return 0;
})->purpose('Retry a Telegram delivery after correcting configuration or connectivity');

Artisan::command('telegram:test {salon : Salon slug} {user : Application owner ID}', function () {
    if (! config('telegram.enabled') || ! config('telegram.bot_token')) {
        $this->error('Enable Telegram and configure the bot token first.');

        return 1;
    }
    $salon = Salon::where('slug', $this->argument('salon'))->where('is_active', true)->first();
    $owner = $salon?->users()->whereKey($this->argument('user'))->where('users.is_active', true)
        ->wherePivot('role', SalonRole::Owner->value)->wherePivot('is_active', true)->first();
    if (! $owner?->pivot->telegram_chat_id) {
        $this->error('Connect an active owner Telegram recipient first.');

        return 1;
    }
    $delivery = TelegramDelivery::create([
        'notification_id' => (string) Str::uuid(), 'salon_id' => $salon->id,
        'user_id' => $owner->id, 'chat_id' => $owner->pivot->telegram_chat_id,
        'payload' => ['salon_name' => $salon->name, 'title' => 'تست اعلان تلگرام', 'message' => 'اتصال ارسال اعلان مدیر برقرار است.', 'path' => '/panel/appointments'],
    ]);
    SendTelegramNotification::dispatch($delivery->id);
    $this->info('Test queued. Delivery ID: '.$delivery->id);

    return 0;
})->purpose('Queue a test Telegram message for a connected salon owner');

Artisan::command('email:connect {salon : Salon slug} {user : Application user ID} {email : Notification recipient email address} {--disconnect}', function () {
    $salon = Salon::where('slug', $this->argument('salon'))->where('is_active', true)->first();
    $owner = $salon?->users()->whereKey($this->argument('user'))->where('users.is_active', true)
        ->wherePivot('role', SalonRole::Owner->value)->wherePivot('is_active', true)->first();
    if (! $owner || ! filter_var($this->argument('email'), FILTER_VALIDATE_EMAIL)) {
        $this->error('An active salon owner and a valid recipient email address are required.');

        return 1;
    }
    $salon->users()->updateExistingPivot($owner->id, ['notification_email' => $this->option('disconnect') ? null : $this->argument('email')]);
    $this->info('Email recipient updated.');

    return 0;
})->purpose('Connect or disconnect a salon owner email recipient');

Artisan::command('email:retry {delivery : Pending or failed delivery ID}', function () {
    if (! config('salon_notifications.email_enabled') || ! app(NotificationEmailTransport::class)->configured()) {
        $this->error('Enable notification email and configure a real SMTP mailer first.');

        return 1;
    }
    $delivery = EmailDelivery::whereKey($this->argument('delivery'))->whereIn('status', ['pending', 'failed'])->first();
    if (! $delivery) {
        $this->error('Pending or failed delivery not found.');

        return 1;
    }
    $delivery->update(['status' => 'pending', 'last_error_code' => null]);
    SendEmailNotification::dispatch($delivery->id);
    $this->info('Delivery queued.');

    return 0;
})->purpose('Retry an email delivery after correcting configuration or connectivity');

Artisan::command('email:test {salon : Salon slug} {user : Application owner ID}', function () {
    if (! config('salon_notifications.email_enabled') || ! app(NotificationEmailTransport::class)->configured()) {
        $this->error('Enable notification email and configure a real SMTP mailer first.');

        return 1;
    }
    $salon = Salon::where('slug', $this->argument('salon'))->where('is_active', true)->first();
    $owner = $salon?->users()->whereKey($this->argument('user'))->where('users.is_active', true)
        ->wherePivot('role', SalonRole::Owner->value)->wherePivot('is_active', true)->first();
    if (! $owner?->pivot->notification_email) {
        $this->error('Connect an active owner email recipient first.');

        return 1;
    }
    $delivery = EmailDelivery::create([
        'notification_id' => (string) Str::uuid(), 'salon_id' => $salon->id,
        'user_id' => $owner->id, 'recipient' => $owner->pivot->notification_email,
        'payload' => ['salon_name' => $salon->name, 'title' => 'تست اعلان ایمیل', 'message' => 'اتصال ارسال ایمیل مدیر برقرار است.', 'path' => '/panel/appointments'],
    ]);
    SendEmailNotification::dispatch($delivery->id);
    $this->info('Test queued. Delivery ID: '.$delivery->id);

    return 0;
})->purpose('Queue a test email message for a connected salon owner');
