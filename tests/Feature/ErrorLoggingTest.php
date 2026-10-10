<?php

namespace Tests\Feature;

use App\Models\ErrorLog;
use App\Services\Errors\DatabaseExceptionReporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ErrorLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_server_errors_without_sensitive_values(): void
    {
        app(DatabaseExceptionReporter::class)->report(
            new \RuntimeException('Failure token=secret mobile=09123456789'),
        );

        $error = ErrorLog::query()->sole();

        $this->assertSame(500, $error->status_code);
        $this->assertSame(\RuntimeException::class, $error->exception_class);
        $this->assertStringNotContainsString('secret', $error->message);
        $this->assertStringNotContainsString('09123456789', $error->message);
        $this->assertNotEmpty($error->reference_id);
    }

    public function test_it_does_not_persist_unrelated_client_errors(): void
    {
        app(DatabaseExceptionReporter::class)->report(new NotFoundHttpException);

        $this->assertDatabaseCount('error_logs', 0);
    }
}
