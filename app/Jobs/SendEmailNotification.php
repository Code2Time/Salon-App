<?php

namespace App\Jobs;

use App\Enums\SalonRole;
use App\Mail\SalonOwnerNotice;
use App\Models\EmailDelivery;
use App\Models\Salon;
use App\Services\Notifications\NotificationEmailTransport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 45;

    public function __construct(public int $deliveryId)
    {
        $this->onConnection('database');
        $this->onQueue(config('salon_notifications.email_queue', 'mail'));
        $this->afterCommit();
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('email-delivery:'.$this->deliveryId))->releaseAfter(30)->expireAfter(90)];
    }

    public function backoff(): array
    {
        return [30, 120, 300, 900];
    }

    public function handle(NotificationEmailTransport $transport): void
    {
        $delivery = EmailDelivery::find($this->deliveryId);
        if (! $delivery || $delivery->status !== 'pending' || ! config('salon_notifications.email_enabled')) {
            return;
        }
        $owner = Salon::query()->where('is_active', true)->find($delivery->salon_id)?->users()
            ->whereKey($delivery->user_id)->where('users.is_active', true)
            ->wherePivot('role', SalonRole::Owner->value)->wherePivot('is_active', true)->first();
        if (! $owner || $owner->pivot->notification_email !== $delivery->recipient) {
            $delivery->update(['status' => 'skipped']);

            return;
        }
        if (! $transport->configured()) {
            $delivery->update(['status' => 'failed', 'last_error_code' => 'mail_not_configured']);

            return;
        }
        $delivery->increment('attempts');
        try {
            $sent = Mail::to($delivery->recipient)->send(new SalonOwnerNotice($delivery->payload));
        } catch (Throwable) {
            $delivery->update(['last_error_code' => 'mail_transport_failed']);
            // SMTP exceptions can contain credentials or server details. Do not chain them.
            throw new \RuntimeException('Notification email transport failed.');
        }
        if (! $sent) {
            $delivery->update(['last_error_code' => 'mail_send_cancelled']);
            throw new \RuntimeException('Notification email was not accepted by the mailer.');
        }
        $delivery->update(['status' => 'sent', 'sent_at' => now(), 'message_id' => $sent->getMessageId(), 'last_error_code' => null]);
    }

    public function failed(?Throwable $exception): void
    {
        EmailDelivery::whereKey($this->deliveryId)->where('status', 'pending')->update(['status' => 'failed']);
    }
}
