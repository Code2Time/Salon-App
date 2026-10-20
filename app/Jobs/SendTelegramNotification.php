<?php

namespace App\Jobs;

use App\Enums\SalonRole;
use App\Models\Salon;
use App\Models\TelegramDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendTelegramNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 25;

    public function __construct(public int $deliveryId)
    {
        $this->onConnection('database');
        $this->onQueue(config('telegram.queue', 'telegram'));
        $this->afterCommit();
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('telegram-delivery:'.$this->deliveryId))->releaseAfter(30)->expireAfter(60)];
    }

    public function backoff(): array
    {
        return [30, 120, 300, 900];
    }

    public function handle(): void
    {
        $delivery = TelegramDelivery::find($this->deliveryId);
        if (! $delivery || $delivery->status !== 'pending' || ! config('telegram.enabled')) {
            return;
        }
        $token = config('telegram.bot_token');
        if (! $token) {
            $delivery->update(['status' => 'failed']);

            return;
        }
        $owner = Salon::query()->where('is_active', true)->find($delivery->salon_id)?->users()
            ->whereKey($delivery->user_id)->where('users.is_active', true)
            ->wherePivot('role', SalonRole::Owner->value)->wherePivot('is_active', true)->first();
        if (! $owner || (string) $owner->pivot->telegram_chat_id !== $delivery->chat_id) {
            $delivery->update(['status' => 'skipped']);

            return;
        }
        $delivery->increment('attempts');
        $data = $delivery->payload;
        $message = mb_substr($data['salon_name']."\n".$data['title']."\n".$data['message'], 0, 3500);
        $body = ['chat_id' => $delivery->chat_id, 'text' => $message];
        $baseUrl = rtrim(config('app.url'), '/');
        if (preg_match('~^https?://~', $baseUrl) && str_starts_with($data['path'], '/panel/')) {
            $body['reply_markup'] = ['inline_keyboard' => [[['text' => 'مشاهده در پنل', 'url' => $baseUrl.$data['path']]]]];
        }
        try {
            $client = Http::connectTimeout(5)->timeout(15);
            if (filled(config('telegram.proxy'))) {
                $client->withOptions(['proxy' => config('telegram.proxy')]);
            }
            $response = $client->post('https://api.telegram.org/bot'.$token.'/sendMessage', $body);
        } catch (ConnectionException) {
            // Never propagate the HTTP exception: its URL contains the bot token.
            throw new \RuntimeException('Telegram connection failed.');
        }
        if ($response->successful() && $response->json('ok') === true) {
            $delivery->update(['status' => 'sent', 'sent_at' => now(), 'message_id' => $response->json('result.message_id'), 'last_error_code' => null]);

            return;
        }
        $code = (int) ($response->json('error_code') ?? $response->status());
        $delivery->update(['last_error_code' => $code]);
        if ($code === 429) {
            $this->release(max(1, (int) $response->json('parameters.retry_after', 60)));

            return;
        }
        if ($code >= 500) {
            throw new \RuntimeException('Telegram service temporarily unavailable.');
        }
        $delivery->update(['status' => 'failed']);
    }

    public function failed(?Throwable $exception): void
    {
        TelegramDelivery::whereKey($this->deliveryId)->where('status', 'pending')->update(['status' => 'failed']);
    }
}
