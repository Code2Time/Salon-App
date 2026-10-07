# وضعیت پروژه

آخرین بررسی: 2026-10-07. این فایل در پایان هر task به‌روز شود.

## وضعیت مشاهده‌شده

- اسکلت Laravel موجود است؛ `composer.json` وابستگی Laravel را با قید `^12.0` اعلام می‌کند.
- React، TypeScript، Vite، MUI و Emotion نصب شده‌اند؛ Type-check و Build موفق‌اند.
- ورودی React در `resources/js/app.tsx`، Router در `resources/js/routes/AppRoutes.tsx` و Theme مشترک در `resources/js/theme/theme.ts` قرار دارند.
- `AdminLayout` و `CustomerLayout` در `resources/js/layouts/` پیاده شده‌اند؛ مسیرهای `/panel` و `/app` فارسی، RTL و Responsive هستند.
- `routes/web.php` پوستهٔ React را برای مسیرهای Frontend تحویل می‌دهد و مسیرهای API و منابع static را از fallback خارج می‌کند. `routes/api.php` هنوز موجود نیست.
- Emotion RTL، فونت محلی Vazirmatn، منوی کشویی پنل، ناوبری پایین مشتری، صفحات جای‌نگهدار و 404 پیاده شده‌اند.
- مدل User و Migrationها و تست‌های نمونهٔ Laravel موجودند. پیاده‌سازی دامنهٔ سالن، رزرو و پرداخت در فایل‌های بررسی‌شده مشاهده نشد.
- هیچ فاز محصول صرفاً با وجود وابستگی‌ها تکمیل‌شده محسوب نمی‌شود.

## وضعیت فازها

| فاز | فایل | وضعیت | توضیح |
| --- | --- | --- | --- |
| 01 | [Panel & Customer Layouts](tasks/01-foundation.md) | done | پیاده‌سازی، Build، Type-check و بررسی مرورگر موفق |
| 01b | [API & Auth](tasks/01b-api-auth.md) | not_started | زیرساخت Backend پس از Layoutها |
| 02 | [Salon Management](tasks/02-salon-management.md) | not_started | وابسته به 01 و 01b |
| 03 | [Appointments](tasks/03-appointments.md) | not_started | وابسته به 02 |
| 04 | [Payments](tasks/04-payments.md) | not_started | وابسته به 03 و تصمیم درگاه |
| 05 | [Customer App](tasks/05-customer-app.md) | not_started | وابسته به 03 و مسیر پیش‌پرداخت 04 |
| 06 | [Advanced Features](tasks/06-advanced-features.md) | needs_scoping | انتخاب زیرکار پیش از اجرا ضروری است |

وضعیت‌های مجاز: `not_started`، `in_progress`، `blocked`، `done`، `needs_scoping`.

## آخرین تحویل

- تاریخ: 2026-10-07؛ تسک `Ai/tasks/01-foundation.md` تکمیل شد.
- دو Layout مستقل، داشبورد اولیه، خانهٔ مشتری، صفحات داخلی جای‌نگهدار، صفحهٔ انتخاب پیش‌نمایش و 404 ساخته شدند. API، احراز هویت و دادهٔ تجاری هنوز اجرا نشده‌اند.
- فایل‌های اصلی: `resources/js/layouts/`، `resources/js/routes/`، `resources/js/pages/`، `resources/js/theme/theme.ts`، `resources/css/layout.css` و `routes/web.php`.
- `npm run typecheck` و `npm run build`: موفق.
- PHPUnit: چهار تست و 35 assertion موفق؛ تست جدید `tests/Feature/LayoutRoutesTest.php` مسیر مستقیم و عدم تصاحب API/static را پوشش می‌دهد.
- PHP این محیط از `C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe` اجرا شد؛ برای تست از گزینه‌های `-d extension_dir=C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/ext -d extension=mbstring -d extension=openssl vendor/phpunit/phpunit/phpunit` استفاده شد، چون افزونه‌های پیش‌فرض CLI فعال نبودند. فایل تنظیمات PHP تغییر نکرد.
- Pint روی `routes/web.php` و `tests/Feature/LayoutRoutesTest.php`: موفق.
- Playwright با Chrome محلی: 18 مسیر در هر سه عرض 360، 768 و 1440 بررسی شدند؛ RTL، نبود سرریز افقی و بارگذاری مستقیم موفق بودند. تصاویر هر دو پوسته در هر سه عرض نیز بازبینی شدند.
- تعامل‌های مرورگر: Drawer راست، Escape، بسته‌شدن با انتخاب مسیر، Refresh، Back/Forward، لینک رزرو، آیتم فعال، دسترسی به لینک با Tab، فضای زیر محتوا برای ناوبری پایین و نبود خطای JavaScript موفق بودند.
- ابزار و تصاویر بررسی مرورگر خارج از مخزن در `C:/Users/Amir/AppData/Local/Temp/ara-layout-check/` قرار دارند. دستور اجرا در همین محیط: `node.exe C:/Users/Amir/AppData/Local/Temp/ara-layout-check/check.cjs` با سرور آزمایشی روی پورت 8879.
- سرور بررسی با Session/Cache موقت `array` اجرا شد؛ `.env` و دیتابیس کاربر تغییر نکردند. تنظیم واقعی دیتابیس/Session برای محیط اجرا باید آماده باشد؛ راهنما: `Ai/LocalDevelopment.md`.
- محدودیت: Safe Area با CSS و فاصلهٔ محتوا بررسی شد؛ تست دستگاه فیزیکی iOS انجام نشده است. Backend تجاری و Provider بیرونی خارج از محدوده‌اند.
- `npm audit` دو مورد critical در زنجیرهٔ ابزار توسعهٔ موجود `concurrently → shell-quote` گزارش کرد؛ اصلاح وابستگی نامرتبط در این تسک انجام نشد.
- قدم بعدی: اجرای `Ai/tasks/01b-api-auth.md` فقط پس از درخواست کاربر.

## قالب ثبت تحویل بعدی

- تاریخ و task:
- وضعیت و معیارهای پذیرش محقق‌شده:
- تغییرات و مسیر فایل‌های مهم:
- تصمیم‌های ثبت‌شده:
- فرمان‌های بررسی و نتایج واقعی:
- موارد بررسی‌نشده یا موانع:
- کار باقی‌مانده و قدم دقیق ادامه:
