<?php

namespace Tests\Feature;

use App\Enums\SalonRole;
use App\Jobs\SendEmailNotification;
use App\Jobs\SendTelegramNotification;
use App\Mail\SalonOwnerNotice;
use App\Models\EmailDelivery;
use App\Models\Salon;
use App\Models\TelegramDelivery;
use App\Models\User;
use App\Notifications\AppointmentBooked;
use App\Notifications\AppointmentCancelledByCustomer;
use App\Notifications\ProductOrderPlaced;
use App\Services\Notifications\NotificationEmailTransport;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    private ArrayTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh')->assertSuccessful();
        RefreshDatabaseState::$migrated = false;
        config(['salon_notifications.email_enabled' => true, 'telegram.enabled' => false,
            'mail.default' => 'smtp', 'mail.from.address' => 'salon@sender.test',
            'mail.mailers.smtp.host' => 'smtp.sender.test', 'app.url' => 'https://salon.example']);
        // Exercise the full email renderer while replacing only the network transport.
        $this->transport = new ArrayTransport;
        Mail::mailer('smtp')->setSymfonyTransport($this->transport);
        Queue::fake();
    }

    private function fixture(): array
    {
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'ara', 'is_active' => true]);
        $owner = User::factory()->create();
        $salon->users()->attach($owner, ['role' => SalonRole::Owner->value, 'is_active' => true, 'notification_email' => 'manager@example.org']);

        return [$salon, $owner];
    }

    private function send(EmailDelivery $delivery): void
    {
        (new SendEmailNotification($delivery->id))->handle(new NotificationEmailTransport);
    }

    public function test_three_events_queue_after_commit_and_rollback_removes_deliveries(): void
    {
        [$salon, $owner] = $this->fixture();
        DB::beginTransaction();
        $owner->notify(new AppointmentBooked($salon->id, 1, 'نوبت جدید'));
        $owner->notify(new AppointmentCancelledByCustomer(1, 'مشتری', null, $salon->id));
        $owner->notify(new ProductOrderPlaced(2, 'مشتری', $salon->id));
        $this->assertDatabaseCount('email_deliveries', 3);
        Queue::assertNothingPushed();
        DB::commit();
        Queue::assertPushed(SendEmailNotification::class, 3);
        Queue::assertPushed(SendEmailNotification::class, fn ($job) => $job->connection === 'database' && $job->queue === 'mail');
        DB::beginTransaction();
        $owner->notify(new ProductOrderPlaced(3, 'مشتری', $salon->id));
        DB::rollBack();
        $this->assertDatabaseCount('email_deliveries', 3);
        Queue::assertPushed(SendEmailNotification::class, 3);
    }

    public function test_only_active_same_salon_owners_with_configured_recipient_are_included(): void
    {
        [$salon, $owner] = $this->fixture();
        $other = Salon::create(['name' => 'دیگر', 'slug' => 'other', 'is_active' => true]);
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $other->id));
        foreach ([['role' => SalonRole::Employee->value], ['role' => SalonRole::Owner->value, 'is_active' => false],
            ['is_active' => true, 'notification_email' => null]] as $changes) {
            $salon->users()->updateExistingPivot($owner->id, $changes);
            $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        }
        $this->assertDatabaseCount('email_deliveries', 0);
        $this->assertDatabaseCount('notifications', 4);
        Queue::assertNothingPushed();
    }

    public function test_success_renders_persian_email_and_cannot_be_replayed(): void
    {
        [$salon, $owner] = $this->fixture();
        $owner->notify(new ProductOrderPlaced(12, 'مشتری', $salon->id));
        $delivery = EmailDelivery::sole();
        $this->send($delivery);
        $this->send($delivery);
        $this->assertCount(1, $this->transport->messages());
        $mail = $this->transport->messages()->first()->getOriginalMessage();
        $this->assertSame('manager@example.org', $mail->getTo()[0]->getAddress());
        $this->assertStringContainsString('سفارش محصول جدید', $mail->getSubject());
        $this->assertStringContainsString('https://salon.example/panel/orders/12', $mail->getHtmlBody());
        $this->assertStringContainsString('مشتری', $mail->getTextBody());
        $this->assertSame('sent', $delivery->fresh()->status);
        $this->assertNotNull($delivery->fresh()->message_id);
    }

    public function test_revoked_or_changed_recipient_does_not_receive_queued_message(): void
    {
        [$salon, $owner] = $this->fixture();
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        $salon->users()->updateExistingPivot($owner->id, ['notification_email' => 'changed@example.org']);
        $delivery = EmailDelivery::sole();
        $this->send($delivery);
        $this->assertSame('skipped', $delivery->fresh()->status);
        $this->assertCount(0, $this->transport->messages());
    }

    public function test_log_mailer_does_not_count_as_real_delivery(): void
    {
        [$salon, $owner] = $this->fixture();
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        config(['mail.default' => 'log']);
        $delivery = EmailDelivery::sole();
        $this->send($delivery);
        $this->assertSame('failed', $delivery->fresh()->status);
        $this->assertSame('mail_not_configured', $delivery->fresh()->last_error_code);
        $this->assertCount(0, $this->transport->messages());
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_transport_failure_is_sanitized_and_does_not_affect_telegram(): void
    {
        [$salon, $owner] = $this->fixture();
        config(['telegram.enabled' => true]);
        $salon->users()->updateExistingPivot($owner->id, ['telegram_chat_id' => '123456']);
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('smtp password=secret'));
        $delivery = EmailDelivery::sole();
        try {
            $this->send($delivery);
            $this->fail('Expected mail failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Notification email transport failed.', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
        $this->assertSame('pending', $delivery->fresh()->status);
        $this->assertSame('pending', TelegramDelivery::sole()->status);
        Queue::assertPushed(SendTelegramNotification::class, 1);
        $job = new SendEmailNotification($delivery->id);
        $job->failed(null);
        $this->assertSame('failed', $delivery->fresh()->status);
    }

    public function test_email_can_be_disabled_while_telegram_stays_enabled(): void
    {
        [$salon, $owner] = $this->fixture();
        config(['salon_notifications.email_enabled' => false, 'telegram.enabled' => true]);
        $salon->users()->updateExistingPivot($owner->id, ['telegram_chat_id' => '123456']);
        $owner->notify(new ProductOrderPlaced(1, 'مشتری', $salon->id));
        $this->assertDatabaseCount('email_deliveries', 0);
        $this->assertDatabaseCount('telegram_deliveries', 1);
    }

    public function test_commands_validate_recipient_and_allow_test_retry_and_disconnect(): void
    {
        [$salon, $owner] = $this->fixture();
        $this->artisan('email:connect', ['salon' => 'ara', 'user' => $owner->id, 'email' => 'invalid'])->assertFailed();
        $this->artisan('email:connect', ['salon' => 'ara', 'user' => $owner->id, 'email' => 'new@example.org'])->assertSuccessful();
        $this->artisan('email:test', ['salon' => 'ara', 'user' => $owner->id])->assertSuccessful();
        $delivery = EmailDelivery::sole();
        $this->assertSame('new@example.org', $delivery->recipient);
        $delivery->update(['status' => 'sent']);
        $this->artisan('email:retry', ['delivery' => $delivery->id])->assertFailed();
        $delivery->update(['status' => 'failed']);
        $this->artisan('email:retry', ['delivery' => $delivery->id])->assertSuccessful();
        $this->artisan('email:connect', ['salon' => 'ara', 'user' => $owner->id, 'email' => 'new@example.org', '--disconnect' => true])->assertSuccessful();
        $this->assertNull($salon->users()->first()->pivot->notification_email);
        config(['mail.default' => 'log']);
        $this->artisan('email:test', ['salon' => 'ara', 'user' => $owner->id])->assertFailed();
    }

    public function test_mailtrap_requires_real_credentials_and_sender_before_queuing_test(): void
    {
        [, $owner] = $this->fixture();
        config(['mail.mailers.smtp.host' => 'live.smtp.mailtrap.io', 'mail.mailers.smtp.username' => 'api']);
        $transport = new NotificationEmailTransport;
        foreach ([null, '', 'null', '<YOUR_API_TOKEN>', 'YOUR_API_TOKEN'] as $password) {
            config(['mail.mailers.smtp.password' => $password]);
            $this->assertFalse($transport->configured());
        }
        $this->artisan('email:test', ['salon' => 'ara', 'user' => $owner->id])->assertFailed();
        $this->assertDatabaseCount('email_deliveries', 0);
        config(['mail.mailers.smtp.password' => 'test-only-mailtrap-credential']);
        $this->assertTrue($transport->configured());
        config(['mail.from.address' => 'YOUR_VERIFIED_SENDER_EMAIL@example.org']);
        $this->assertFalse($transport->configured());
        config(['mail.from.address' => 'notifications@verified.example.org', 'mail.mailers.smtp.username' => null]);
        $this->assertFalse($transport->configured());
    }

    public function test_email_template_escapes_customer_content(): void
    {
        $mail = new SalonOwnerNotice(['salon_name' => 'آرا', 'title' => 'نوبت جدید', 'message' => '<script>alert(1)</script>', 'path' => '/panel/appointments']);
        $html = $mail->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
