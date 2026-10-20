<?php

namespace App\Listeners;

use App\Enums\SalonRole;
use App\Jobs\SendEmailNotification;
use App\Models\EmailDelivery;
use App\Models\Salon;
use App\Models\User;
use App\Notifications\AppointmentBooked;
use App\Notifications\AppointmentCancelledByCustomer;
use App\Notifications\ProductOrderPlaced;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class QueueEmailNotification
{
    public function handle(NotificationSent $event): void
    {
        if (! config('salon_notifications.email_enabled') || $event->channel !== 'database'
            || ! $event->notifiable instanceof User
            || ! in_array($event->notification::class, [AppointmentBooked::class, AppointmentCancelledByCustomer::class, ProductOrderPlaced::class], true)) {
            return;
        }

        $payload = $event->response->data;
        $salon = Salon::query()->where('is_active', true)->find($payload['salon_id'] ?? null);
        $owner = $salon?->users()->whereKey($event->notifiable->id)->where('users.is_active', true)
            ->wherePivot('role', SalonRole::Owner->value)->wherePivot('is_active', true)->first();
        $recipient = $owner?->pivot->notification_email;
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $delivery = EmailDelivery::firstOrCreate(
            ['notification_id' => $event->response->id, 'user_id' => $owner->id],
            ['salon_id' => $salon->id, 'recipient' => $recipient, 'payload' => [...$payload, 'salon_name' => $salon->name]],
        );
        DB::afterCommit(function () use ($delivery): void {
            try {
                SendEmailNotification::dispatch($delivery->id);
            } catch (Throwable) {
                // The durable pending delivery can be recovered without failing the booking/order.
                Log::warning('Email delivery could not be queued.', ['delivery_id' => $delivery->id]);
            }
        });
    }
}
