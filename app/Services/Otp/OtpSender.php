<?php

namespace App\Services\Otp;

interface OtpSender
{
    public function send(string $mobile, string $code): void;
}
