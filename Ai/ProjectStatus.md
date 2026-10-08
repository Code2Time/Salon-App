# وضعیت پروژه

آخرین بررسی: 2026-10-08. این فایل در پایان هر task به‌روز شود.

## وضعیت مشاهده‌شده

- اسکلت Laravel موجود است؛ `composer.json` وابستگی Laravel را با قید `^12.0` اعلام می‌کند.
- React، TypeScript، Vite، MUI و Emotion نصب شده‌اند؛ Type-check و Build موفق‌اند.
- ورودی React در `resources/js/app.tsx`، Router در `resources/js/routes/AppRoutes.tsx` و Theme مشترک در `resources/js/theme/theme.ts` قرار دارند.
- `AdminLayout` و `CustomerLayout` در `resources/js/layouts/` پیاده شده‌اند؛ مسیرهای `/panel` و `/app` فارسی، RTL و Responsive هستند.
- `routes/web.php` پوستهٔ React را برای مسیرهای Frontend تحویل می‌دهد و `routes/api.php` قرارداد احراز هویت و Context پنل را ارائه می‌کند.
- Emotion RTL، فونت محلی Vazirmatn، منوی کشویی پنل، ناوبری پایین مشتری، صفحات جای‌نگهدار و 404 پیاده شده‌اند.
- مدل User موبایل‌محور، مدل حداقلی Salon، عضویت نقش‌دار، OTP و جداسازی زمینهٔ سالن پیاده شده‌اند. رزرو و پرداخت هنوز پیاده نشده‌اند.
- هیچ فاز محصول صرفاً با وجود وابستگی‌ها تکمیل‌شده محسوب نمی‌شود.

## وضعیت فازها

| فاز | فایل | وضعیت | توضیح |
| --- | --- | --- | --- |
| 01 | [Panel & Customer Layouts](tasks/01-foundation.md) | done | پیاده‌سازی، Build، Type-check و بررسی مرورگر موفق |
| 01b | [API & Auth](tasks/01b-api-auth.md) | done | OTP توسعه‌ای، Session/Sanctum، نقش‌ها، سالن و کنترل Layout/API |
| 02 | [Salon Management](tasks/02-salon-management.md) | not_started | وابسته به 01 و 01b |
| 03 | [Appointments](tasks/03-appointments.md) | not_started | وابسته به 02 |
| 04 | [Payments](tasks/04-payments.md) | not_started | وابسته به 03 و تصمیم درگاه |
| 05 | [Customer App](tasks/05-customer-app.md) | not_started | وابسته به 03 و مسیر پیش‌پرداخت 04 |
| 06 | [Advanced Features](tasks/06-advanced-features.md) | needs_scoping | انتخاب زیرکار پیش از اجرا ضروری است |

وضعیت‌های مجاز: `not_started`، `in_progress`، `blocked`، `done`، `needs_scoping`.

## آخرین تحویل

- تاریخ: 2026-10-08؛ تسک `Ai/tasks/01b-api-auth.md` تکمیل شد.
- Sanctum مبتنی بر Cookie/Session، درخواست و تأیید OTP، ثبت‌نام مشتری، ورود، خروج و دریافت کاربر جاری پیاده شدند. Session روی ۹۰۰ دقیقه تنظیم شد.
- مدل‌های `User`، `Salon`، `SalonUser` و `OtpChallenge` و migrationهای مربوط اضافه شدند. نقش Super Admin سراسری و نقش‌های Owner/Manager/Employee در عضویت سالن قرار دارند.
- Route Guardهای React کاربر را بر اساس نقش به `/panel` یا `/app` هدایت می‌کنند؛ منوی پنل نیز برای Employee، Manager، Owner و Super Admin محدود می‌شود.
- خطاها و موفقیت‌های احراز هویت با React Toastify نمایش داده می‌شوند؛ ترجمهٔ مرکزی فارسی برای خطاهای API از جمله محدودیت درخواست 429 وجود دارد و پیام خام انگلیسی Backend به کاربر نمایش داده نمی‌شود.
- پالت UI به تمشکی/رز/آلوئی با Accent طلایی ارتقا یافته و Theme مرکزی، فرم ورود، پنل و اپ مشتری هماهنگ شده‌اند.
- مدل User فیلد nullable تصویر و API فیلد `image_url` دارد؛ Avatar در Header دو Layout نمایش داده می‌شود. Super Admin امکان جابه‌جایی سریع بین پنل و اپ مشتری را دارد.
- شماره موبایل در Database با قالب محلی یازده‌رقمی `09xxxxxxxxx` ذخیره می‌شود؛ migration داده‌های قبلی `98...` را تبدیل کرده و ورودی موبایل UI حداکثر ۱۱ کاراکتر دارد.
- اپ مشتری برای مشاهده عمومی نیازمند ورود نیست؛ فقط رزرو، نوبت‌های من و پروفایل Guard شده‌اند و کاربر پس از OTP به مقصد قبلی بازمی‌گردد. Header مهمان ورودی‌های Login/Register دارد.
- پروفایل پایه کاربر شامل نام، نام خانوادگی، تاریخ تولد nullable، موبایل، ایمیل nullable و تصویر است؛ ثبت‌نام نام و نام خانوادگی را جداگانه دریافت می‌کند.
- Seeder پنج حساب آزمایشی با شماره‌های `09111111001` تا `09111111005` می‌سازد؛ ترتیب نقش‌ها Super Admin، Owner، Manager، Employee و Customer است و OTP همه در توسعه `1111` است.
- فایل‌های اصلی Backend: `routes/api.php`، `app/Http/Controllers/Api/Auth/`، `app/Http/Middleware/`، `app/Models/`، `app/Services/Otp/` و migrationهای تاریخ 2026-10-08.
- فایل‌های اصلی Frontend: `resources/js/auth/`، `resources/js/pages/auth/`، `resources/js/components/auth/` و `resources/js/services/api/`.
- PHPUnit: ۱۵ تست و ۸۴ assertion موفق؛ ثبت‌نام امن، فیلدهای پروفایل، OTP نامعتبر/مصرف‌شده، Session، خروج، دسترسی نقش و جلوگیری از دسترسی بین سالن‌ها پوشش داده شدند.
- `npm run typecheck` و `npm run build`: موفق. Build فقط هشدار غیرمسدودکنندهٔ chunk بزرگ‌تر از 500 kB دارد.
- Pint روی ۵۳ فایل موفق است. پنج Route مربوط به Auth و Context پنل ثبت شده‌اند.
- migrationهای MySQL تا `2026_10_08_000006` روی دیتابیس `.env` با موفقیت اجرا شدند؛ داده‌های موبایل موجود تبدیل و فیلدهای پروفایل هر ۶ کاربر موجود backfill شدند.
- Provider واقعی پیامک هنوز تهیه نشده و اتصال SMS تأیید نشده است. Fixed Provider خارج از local/testing قابل استفاده نیست.
- قدم بعدی: اجرای فاز 02 فقط پس از درخواست کاربر.

## قالب ثبت تحویل بعدی

- تاریخ و task:
- وضعیت و معیارهای پذیرش محقق‌شده:
- تغییرات و مسیر فایل‌های مهم:
- تصمیم‌های ثبت‌شده:
- فرمان‌های بررسی و نتایج واقعی:
- موارد بررسی‌نشده یا موانع:
- کار باقی‌مانده و قدم دقیق ادامه:
