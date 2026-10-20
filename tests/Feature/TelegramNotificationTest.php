<?php

namespace Tests\Feature;

use App\Enums\SalonRole;
use App\Jobs\SendTelegramNotification;
use App\Models\Salon;
use App\Models\TelegramDelivery;
use App\Models\User;
use App\Notifications\AppointmentBooked;
use App\Notifications\AppointmentCancelledByCustomer;
use App\Notifications\ProductOrderPlaced;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TelegramNotificationTest extends TestCase
{
    private mixed $actualQueue;

    protected function setUp(): void
    {
        parent::setUp();
        // Use real commits; RefreshDatabase wraps tests in an outer transaction.
        $this->artisan('migrate:fresh')->assertSuccessful();
        RefreshDatabaseState::$migrated = false;
        config(['telegram.enabled' => true, 'telegram.bot_token' => 'test-secret', 'telegram.proxy' => null, 'app.url' => 'https://salon.example']);
        Http::preventStrayRequests();
        $this->actualQueue = Queue::getFacadeRoot();
        Queue::fake();
    }

    private function fixture(): array
    {
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'ara', 'is_active' => true]);
        $owner = User::factory()->create();
        $salon->users()->attach($owner, ['role' => SalonRole::Owner->value, 'is_active' => true, 'telegram_chat_id' => '123456']);

        return [$salon, $owner];
    }

    public function test_all_three_owner_events_create_durable_deliveries_and_queue_after_commit(): void
    {
        [$salon, $owner] = $this->fixture();
        DB::beginTransaction();
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        $owner->notify(new AppointmentCancelledByCustomer(2, 'مشتری', 'تغییر برنامه', $salon->id));
        $owner->notify(new AppointmentBooked($salon->id, 3, 'نوبت جدید'));
        Queue::assertNothingPushed();
        $this->assertDatabaseCount('telegram_deliveries', 3);
        DB::commit();
        Queue::assertPushed(SendTelegramNotification::class, 3);
        Queue::assertPushed(SendTelegramNotification::class, fn ($job) => $job->connection === 'database' && $job->queue === 'telegram');
        Http::assertNothingSent();
    }

    public function test_rollback_does_not_send_or_keep_delivery(): void
    {
        [$salon, $owner] = $this->fixture();
        DB::beginTransaction();
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        DB::rollBack();
        $this->assertDatabaseCount('telegram_deliveries', 0);
        Queue::assertNothingPushed();
    }

    public function test_disabled_unconnected_employee_and_other_salon_recipients_are_excluded(): void
    {
        [$salon, $owner] = $this->fixture();
        $other = Salon::create(['name' => 'دیگر', 'slug' => 'other', 'is_active' => true]);
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $other->id));
        $salon->users()->updateExistingPivot($owner->id, ['role' => SalonRole::Employee->value]);
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        $salon->users()->updateExistingPivot($owner->id, ['role' => SalonRole::Owner->value, 'telegram_chat_id' => null]);
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        $salon->users()->updateExistingPivot($owner->id, ['telegram_chat_id' => '123456']);
        config(['telegram.enabled' => false]);
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        $this->assertDatabaseCount('notifications', 4);
        $this->assertDatabaseCount('telegram_deliveries', 0);
        Queue::assertNothingPushed();
    }

    public function test_success_is_recorded_and_repeated_job_does_not_send_again(): void
    {
        [$salon, $owner] = $this->fixture();
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 42]])]);
        $delivery = TelegramDelivery::sole();
        $job = new SendTelegramNotification($delivery->id);
        $job->handle();
        $job->handle();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['chat_id'] === '123456'
            && $request['reply_markup']['inline_keyboard'][0][0]['url'] === 'https://salon.example/panel/orders/1');
        $this->assertSame('sent', $delivery->fresh()->status);
        $this->assertSame('42', $delivery->fresh()->message_id);
    }

    public function test_optional_proxy_is_applied_only_to_telegram_request(): void
    {
        [$salon, $owner] = $this->fixture();
        config(['telegram.proxy' => 'http://127.0.0.1:10808']);
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        Http::fake(function ($request, $options) {
            $this->assertSame('http://127.0.0.1:10808', $options['proxy']);

            return Http::response(['ok' => true, 'result' => ['message_id' => 43]]);
        });
        (new SendTelegramNotification(TelegramDelivery::sole()->id))->handle();
        $this->assertSame('sent', TelegramDelivery::sole()->status);
    }

    public function test_revoked_membership_is_checked_again_before_sending(): void
    {
        [$salon, $owner] = $this->fixture();
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        $salon->users()->updateExistingPivot($owner->id, ['is_active' => false]);
        $delivery = TelegramDelivery::sole();
        (new SendTelegramNotification($delivery->id))->handle();
        Http::assertNothingSent();
        $this->assertSame('skipped', $delivery->fresh()->status);
    }

    public function test_blocked_bot_is_terminal_and_database_notice_is_preserved(): void
    {
        [$salon, $owner] = $this->fixture();
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'error_code' => 403, 'description' => 'blocked'], 403)]);
        $delivery = TelegramDelivery::sole();
        (new SendTelegramNotification($delivery->id))->handle();
        $this->assertSame('failed', $delivery->fresh()->status);
        $this->assertSame(403, $delivery->fresh()->last_error_code);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_connection_errors_do_not_expose_bot_token_and_remain_retryable(): void
    {
        [$salon, $owner] = $this->fixture();
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        Http::fake(['api.telegram.org/*' => Http::failedConnection()]);
        $delivery = TelegramDelivery::sole();
        try {
            (new SendTelegramNotification($delivery->id))->handle();
            $this->fail('Expected retryable exception.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Telegram connection failed.', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
        $this->assertSame('pending', $delivery->fresh()->status);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_rate_limit_respects_telegram_retry_after(): void
    {
        [$salon, $owner] = $this->fixture();
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'error_code' => 429, 'parameters' => ['retry_after' => 75]], 429)]);
        $job = (new SendTelegramNotification(TelegramDelivery::sole()->id))->withFakeQueueInteractions();
        $job->handle();
        $job->assertReleased(75);
    }

    public function test_temporary_server_failure_is_retryable_and_exhaustion_is_recorded(): void
    {
        [$salon, $owner] = $this->fixture();
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'error_code' => 503], 503)]);
        $delivery = TelegramDelivery::sole();
        $job = new SendTelegramNotification($delivery->id);
        try {
            $job->handle();
            $this->fail('Expected a retryable service error.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Telegram service temporarily unavailable.', $exception->getMessage());
        }
        $this->assertSame('pending', $delivery->fresh()->status);
        $job->failed(null);
        $this->assertSame('failed', $delivery->fresh()->status);
    }

    public function test_test_and_retry_commands_queue_only_eligible_deliveries(): void
    {
        [, $owner] = $this->fixture();
        $this->artisan('telegram:test', ['salon' => 'ara', 'user' => $owner->id])->assertSuccessful();
        $delivery = TelegramDelivery::sole();
        $this->assertDatabaseCount('notifications', 0);
        $delivery->update(['status' => 'sent']);
        $this->artisan('telegram:retry', ['delivery' => $delivery->id])->assertFailed();
        $delivery->update(['status' => 'failed']);
        $this->artisan('telegram:retry', ['delivery' => $delivery->id])->assertSuccessful();
        $this->assertSame('pending', $delivery->fresh()->status);
        Queue::assertPushed(SendTelegramNotification::class, 2);
    }

    public function test_committed_delivery_is_stored_in_database_queue_without_network_request(): void
    {
        [$salon, $owner] = $this->fixture();
        Queue::swap($this->actualQueue);
        DB::beginTransaction();
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        $this->assertDatabaseCount('jobs', 0);
        DB::commit();
        $this->assertDatabaseHas('jobs', ['queue' => 'telegram']);
        Http::assertNothingSent();
    }

    public function test_connect_command_only_accepts_owner_and_numeric_private_chat(): void
    {
        [$salon, $owner] = $this->fixture();
        $this->artisan('telegram:connect', ['salon' => 'ara', 'user' => $owner->id, 'chat' => '@username'])->assertFailed();
        $this->artisan('telegram:connect', ['salon' => 'ara', 'user' => $owner->id, 'chat' => '998877'])->assertSuccessful();
        $this->assertSame('998877', $salon->users()->first()->pivot->telegram_chat_id);
        $salon->users()->updateExistingPivot($owner->id, ['role' => SalonRole::Employee->value]);
        $this->artisan('telegram:connect', ['salon' => 'ara', 'user' => $owner->id, 'chat' => '998877'])->assertFailed();
    }
}
