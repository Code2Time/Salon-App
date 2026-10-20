<?php

namespace App\Listeners;

use App\Enums\SalonRole;
use App\Jobs\SendTelegramNotification;
use App\Models\Salon;
use App\Models\TelegramDelivery;
use App\Models\User;
use App\Notifications\AppointmentBooked;
use App\Notifications\AppointmentCancelledByCustomer;
use App\Notifications\ProductOrderPlaced;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class QueueTelegramNotification
{
    public function handle(NotificationSent $event): void
    {
        if (! config('telegram.enabled') || $event->channel !== 'database'
            || ! $event->notifiable instanceof User
            || ! in_array($event->notification::class, [AppointmentBooked::class, AppointmentCancelledByCustomer::class, ProductOrderPlaced::class], true)) {
            return;
        }

        $payload = $event->response->data;
        $salon = Salon::query()->where('is_active', true)->find($payload['salon_id'] ?? null);
        $owner = $salon?->users()->whereKey($event->notifiable->id)->where('users.is_active', true)
            ->wherePivot('role', SalonRole::Owner->value)->wherePivot('is_active', true)->first();
        $chatId = $owner?->pivot->telegram_chat_id;
        if (! $chatId) {
            return;
        }

        $delivery = TelegramDelivery::firstOrCreate(
            ['notification_id' => $event->response->id, 'user_id' => $owner->id],
            ['salon_id' => $salon->id, 'chat_id' => $chatId, 'payload' => [...$payload, 'salon_name' => $salon->name]],
        );
        DB::afterCommit(function () use ($delivery): void {
            try {
                SendTelegramNotification::dispatch($delivery->id);
            } catch (Throwable) {
                // The durable pending delivery can be recovered without failing the booking/order.
                Log::warning('Telegram delivery could not be queued.', ['delivery_id' => $delivery->id]);
            }
        });
    }
}
