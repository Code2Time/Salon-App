<?php

namespace App\Services\Errors;

use App\Models\ErrorLog;
use App\Services\CurrentSalon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class DatabaseExceptionReporter
{
    public function report(Throwable $exception): void
    {
        $request = app()->bound('request') ? request() : null;
        $statusCode = match (true) {
            $exception instanceof ValidationException => 422,
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            default => 500,
        };

        if ($statusCode < 500 && ! $this->isOtpVerificationError($exception, $request)) {
            return;
        }

        try {
            ErrorLog::query()->create([
                'reference_id' => (string) Str::uuid(),
                'environment' => app()->environment(),
                'exception_class' => $exception::class,
                'message' => $this->redact(Str::limit($this->message($exception), 10000, '…')),
                'status_code' => $statusCode,
                'request_method' => $request?->method(),
                'request_path' => $this->requestPath($request),
                'route_name' => $request?->route()?->getName(),
                'user_id' => Auth::id(),
                'salon_id' => $this->salonId(),
                'file' => Str::limit($exception->getFile(), 255, ''),
                'line' => $exception->getLine(),
                'stack_trace' => Str::limit($exception->getTraceAsString(), 65000, '…'),
            ]);
        } catch (Throwable) {
            error_log(sprintf('Could not persist application error [%s].', $exception::class));
        }
    }

    private function requestPath(?Request $request): ?string
    {
        if (! $request) {
            return null;
        }

        return '/'.ltrim($request->path(), '/');
    }

    private function isOtpVerificationError(Throwable $exception, ?Request $request): bool
    {
        return $exception instanceof ValidationException
            && $request?->is('api/auth/otp/verify');
    }

    private function message(Throwable $exception): string
    {
        if (! $exception instanceof ValidationException) {
            return $exception->getMessage();
        }

        return collect($exception->errors())
            ->flatten()
            ->filter(fn (mixed $message): bool => is_string($message))
            ->implode(' | ');
    }

    private function salonId(): ?int
    {
        try {
            return app(CurrentSalon::class)->get()->getKey();
        } catch (Throwable) {
            return null;
        }
    }

    private function redact(string $message): string
    {
        $message = preg_replace(
            '/(password|password_confirmation|token|authorization|cookie|otp|code)(\s*[=:]\s*)[^\s,;}]+/iu',
            '$1$2[REDACTED]',
            $message,
        ) ?? $message;

        return preg_replace('/(?<!\d)(09\d{9})(?!\d)/', '09*******00', $message) ?? $message;
    }
}
