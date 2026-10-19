<?php

namespace App\Services\Otp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class SmsIrOtpSender implements OtpSender
{
    public function send(string $mobile, string $code): void
    {
        if (config('otp.driver') === 'smsir_sandbox' && ! app()->environment(['local', 'testing'])) {
            throw new LogicException('محیط آزمایشی پیامک فقط در توسعه قابل استفاده است.');
        }

        $key = config('otp.smsir.api_key');
        $templateId = (int) config('otp.smsir.template_id');
        $parameter = config('otp.smsir.parameter_name');

        if (! filled($key) || $templateId <= 0 || ! filled($parameter)) {
            throw new HttpException(503, 'تنظیمات سرویس پیامک کامل نشده است.');
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['X-API-KEY' => $key])
                ->connectTimeout(5)
                ->timeout(15)
                ->post('https://api.sms.ir/v1/send/verify', [
                    'mobile' => $mobile,
                    'templateId' => $templateId,
                    'parameters' => [['name' => $parameter, 'value' => $code]],
                ]);
        } catch (ConnectionException) {
            throw new HttpException(503, 'ارتباط با سرویس پیامک برقرار نشد؛ دوباره تلاش کنید.');
        }

        if (! $response->successful() || (int) $response->json('status') !== 1) {
            throw new HttpException(503, 'ارسال کد ورود ناموفق بود؛ دوباره تلاش کنید.');
        }
    }
}
