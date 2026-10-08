<?php

namespace App\Services\Otp;

use LogicException;

final class FixedOtpSender implements OtpSender
{
    public function send(string $mobile, string $code): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('ارائه‌دهنده پیامک برای این محیط تنظیم نشده است.');
        }
    }
}
