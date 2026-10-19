# تنظیم OTP و sms.ir

## حالت‌های ارسال

- `OTP_DRIVER=fixed`: بدون تماس با سرویس پیامک؛ فقط در local/testing. کد ثابت توسعه `2244` است.
- `OTP_DRIVER=smsir_sandbox`: فراخوانی API با کلید Sandbox و کد ثابت `2244`؛ فقط در local/testing.
- `OTP_DRIVER=smsir`: ارسال OTP چهاررقمی تصادفی با کلید واقعی و قالب تأییدشده. در این حالت کد ثابت راه ورود جایگزین نیست.

## تنظیم محلی

کلید را فقط در `.env` بگذار، نه فایل‌های Git یا کد Frontend:

```dotenv
OTP_DRIVER=smsir_sandbox
OTP_FIXED_CODE=2244
SMSIR_API_KEY=your-sandbox-key
SMSIR_TEMPLATE_ID=your-template-id
SMSIR_PARAMETER_NAME=CODE
```

شناسه قالب و نام دقیق پارامتر کد باید از پنل sms.ir برداشته شوند. قالب Sandbox بررسی‌شده `123456` است و پارامتر آن دقیقاً `CODE` است. برای قالب واقعی نیز نام را با پنل تطبیق بده. سپس اجرا کن:

```bash
php artisan config:clear
```

برای بازگشت به ورود ثابت، `OTP_DRIVER=fixed` را تنظیم و کش تنظیمات را پاک کن. Sandbox شبیه‌سازی سرویس است؛ موفقیت درخواست آن اثبات دریافت پیامک واقعی روی گوشی نیست.

درخواست Verify به `https://api.sms.ir/v1/send/verify` با هدر `X-API-KEY` و فیلدهای `mobile`، `templateId` و `parameters` ارسال می‌شود. خطای HTTP، پاسخ ناموفق سرویس یا قطع ارتباط، Challenge را حذف می‌کند و پیام عمومی فارسی برمی‌گرداند؛ متن پاسخ سرویس و کلید در پیام خطا قرار نمی‌گیرند.

مرجع قرارداد: [SDK رسمی sms.ir](https://github.com/IPeCompany/SmsPanelV2.TypeScript).

## وضعیت فعلی

در 2026-10-19 قالب `123456` با `GET /v1/templates/123456` بررسی شد: پاسخ HTTP 200 و status=1، عنوان Sandbox و پارامتر `CODE`. تنظیم محلی روی `smsir_sandbox` با این قالب قرار گرفت و فراخوانی واقعی API ارسال Verify با کد `2244` موفق شد. این نتیجه فقط تأیید Sandbox است و به معنی تحویل پیامک واقعی نیست. برای ورود بدون تماس API همچنان می‌توان `OTP_DRIVER=fixed` را انتخاب کرد. تست‌های خودکار مستقل از درایور محلی اجرا می‌شوند.
