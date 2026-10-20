# اعلان ایمیل مدیر در کنار تلگرام

## رفتار

سه رویداد نوبت جدید، لغو توسط مشتری و سفارش محصول، علاوه بر اعلان داخل برنامه، روی دو کانال مستقل ایمیل و تلگرام قابل ارسال‌اند. هر کانال تنظیم فعال‌سازی، صف، گیرنده و سابقه مستقل دارد؛ موفقیت یا شکست یکی، شرط ارسال دیگری نیست. گیرنده ایمیل در `salon_user.notification_email` و فقط برای مدیر فعال همان سالن ثبت می‌شود؛ ایمیل ورود/پروفایل کاربر تغییر نمی‌کند. گیرنده سراسری برای تمام سالن‌ها نداریم.

در محیط توسعه، `code2time@gmail.com` برای حساب مدیر سالن ara متصل شده است. این آدرس **گیرنده** است؛ تعیین آن دسترسی ارسال از Gmail یا تنظیم SMTP ایجاد نمی‌کند. تنظیمات عمومی Mailtrap live SMTP اعمال شده‌اند؛ توکن فرستنده و آدرس فرستنده تأییدشده هنوز ارائه نشده و ارسال واقعی تأیید نشده است.

## پیکربندی Mailtrap SMTP

```dotenv
SALON_NOTIFICATION_EMAIL_ENABLED=true
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=live.smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=api
MAIL_PASSWORD=<YOUR_API_TOKEN>
MAIL_FROM_ADDRESS=<YOUR_VERIFIED_SENDER_EMAIL>
MAIL_FROM_NAME="سالن آرا"
MAIL_TIMEOUT=20
```

مقادیر نمونه را با اطلاعات سرویس واقعی جایگزین کن. Scheme و پورت باید مطابق سرویس فرستنده باشند. رمزها فقط در `.env` سرور باشند. از SMTP یا صندوق فرستنده مجاز استفاده شود؛ ایمیل گیرنده الزاماً فرستنده نیست. TLS غیرفعال نشود. حالت‌های log و array و failover-to-log در سیستم اعلان ایمیل به‌عنوان ارسال واقعی پذیرفته نمی‌شوند؛ در نبود SMTP مناسب، سابقه با failed و mail_not_configured ثبت می‌شود و امکان Retry پس از تنظیم وجود دارد.

```sh
php artisan migrate
php artisan config:clear
php artisan email:connect ara APP_USER_ID code2time@gmail.com
php artisan queue:work database --queue=mail,telegram,default --timeout=60
```

`composer dev` نیز هر سه صف را گوش می‌دهد. برای استقرار Worker باید با Supervisor یا systemd دائماً اجرا شود. پس از تغییر تنظیمات یا کد `php artisan queue:restart` اجرا شود.

## آزمایش و پیگیری

```sh
php artisan email:test ara APP_USER_ID
php artisan email:retry DELIVERY_ID
php artisan email:connect ara APP_USER_ID code2time@gmail.com --disconnect
```

تست پیش از وجود SMTP واقعی صف نمی‌شود و پیام راه‌اندازی نمایش می‌دهد. جدول `email_deliveries` شامل pending/sent/failed/skipped، تعداد تلاش، کد خطای امن و message_id است. sent فقط پذیرش توسط سرویس SMTP را نشان می‌دهد؛ تحویل به Inbox یا Spam باید در صندوق گیرنده یا گزارش سرویس بررسی شود. متن فارسی، نسخه HTML با RTL و نسخه plain text، و لینک پنل مبتنی بر APP_URL ساخته می‌شوند.

اعلان در تراکنش ثبت می‌شود و Job پس از Commit به صف database/mail می‌رود؛ Rollback ارسال ندارد. دسترسی مدیر و تطابق گیرنده هنگام اجرا مجدداً بررسی می‌شود. شکست انتقال تا ۵ بار با فاصله افزایشی تکرار می‌شود؛ استثنای خام SMTP و اعتبارنامه در خطاهای سیستم ذخیره نمی‌شوند. رکورد موفق بازپخش نمی‌شود و قفل ارسال هم‌زمان اعمال می‌شود. اگر سرویس پیام را بپذیرد اما ثبت نتیجه در دیتابیس شکست بخورد، تکرار در Retry ممکن است؛ تضمین Exactly Once وجود ندارد.

فعال‌سازی تلگرام مستقل با TELEGRAM_ENABLED و راهنمای TelegramSetup.md است. پیام‌های قدیمی کانال دیگر خودکار به ایمیل تبدیل یا بازپخش نمی‌شوند.

## مقادیری که پیش از اجرای واقعی باید جایگزین شوند

- `<YOUR_API_TOKEN>`: مقدار SMTP Password / API token مربوط به Transactional Sending دامنه فرستنده در Mailtrap؛ آن را فقط در MAIL_PASSWORD فایل .env محلی/سرور قرار بده.
- `<YOUR_VERIFIED_SENDER_EMAIL>`: آدرس فرستنده روی دامنه‌ای که در Mailtrap تأیید کرده‌ای، برای نمونه notifications@yourdomain.com. این مقدار ایمیل گیرنده مدیر نیست.
- `APP_USER_ID` در فرمان‌های بالا: شناسه حساب مدیر در برنامه؛ برای مدیر فعلی ara برابر 1 است. `DELIVERY_ID` فقط هنگام تلاش مجدد، شناسه رکورد email_deliveries است.
- APP_URL باید آدرس قابل دسترس پنل باشد تا لینک ایمیل روی دستگاه گیرنده باز شود.

Host، پورت 587 و نام کاربری api از نمونه رسمی Mailtrap هستند؛ MAIL_SCHEME=smtp برای این پورت و STARTTLS خودکار کلاینت موجود استفاده می‌شود. اگر MAIL_URL قبلاً تنظیم شده، آن را پاک کن یا به همین اتصال Mailtrap تغییر بده؛ URL در Laravel می‌تواند تنظیمات جداگانه را بازنویسی کند. برای Mailtrap live، توکن خالی و مقادیر جای‌نگهدار پیش از صف‌کردن تست رد می‌شوند. .env.example نمونه عمومی را نگه می‌دارد و هیچ راز واقعی در Git ثبت نمی‌شود.

پس از تنظیم دو مقدار واقعی:

```sh
php artisan config:clear
php artisan queue:restart
php artisan email:test ara 1
php artisan queue:work database --queue=mail --once --timeout=60
```

فرمان Worker بالا یک Job موجود را پردازش می‌کند؛ برای پردازش دائمی همه کانال‌ها فرمان Worker بخش قبل را اجرا کن. نتیجه را در email_deliveries، صندوق گیرنده و [Mailtrap Email Logs](https://mailtrap.io/sending/email_logs) بررسی کن. وضعیت sent برنامه پذیرش SMTP است و جای گزارش Delivery را نمی‌گیرد.

مراجع: [SMTP Integration](https://docs.mailtrap.io/email-api-smtp/setup/smtp-integration.md)، [Getting Started](https://docs.mailtrap.io/getting-started/email-api-smtp.md)، [فهرست مستندات](https://docs.mailtrap.io/llms.txt).
