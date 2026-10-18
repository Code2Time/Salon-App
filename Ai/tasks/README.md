# نقشهٔ taskها

مسیرها نسبت به ریشهٔ پروژه‌اند. پیش از اجرا `Ai/README.md` خوانده شود. وضعیت اجرای فازها فقط در `Ai/ProjectStatus.md` نگهداری می‌شود؛ این فایل نقشهٔ ترتیب است.

| ترتیب | فایل | هدف | پیش‌نیاز |
| --- | --- | --- | --- |
| 01 | [01-foundation.md](01-foundation.md) | Layout پنل و اپ مشتری، RTL و مسیریابی | بررسی کد فعلی |
| 01b | [01b-api-auth.md](01b-api-auth.md) | API، زمینهٔ سالن و احراز هویت | 01 |
| 01c | [01c-error-logging.md](01c-error-logging.md) | ثبت امن خطاهای Backend در دیتابیس | 01b |
| 02 | [02-salon-management.md](02-salon-management.md) | اطلاعات پایهٔ سالن و داشبورد اولیه | 01 و 01b |
| 02a | [02a-panel-users.md](02a-panel-users.md) | کاربران پنل، نقش و عضویت سالن | 01 و 01b |
| 02b | [02b-service-categories.md](02b-service-categories.md) | CRUD دسته‌بندی خدمات و رابطه چندبه‌چند خدمات | 02a |
| 02c | [02c-service-management.md](02c-service-management.md) | CRUD خدمات، دسته‌بندی‌ها و کارکنان ارائه‌دهنده | 02b |
| 02d | [02d-salon-dashboard.md](02d-salon-dashboard.md) | داشبورد عملیاتی متکی به آمار واقعی سالن | 02c و 03c |
| 03 | [03-appointments.md](03-appointments.md) | رزرو، زمان آزاد و تقویم | 02 |
| 03a | [03a-appointment-settings.md](03a-appointment-settings.md) | منوی بازشونده و تقویم کاری سالن | بخش‌های کاربران و خدمات 02 |
| 03b | [03b-employee-availability.md](03b-employee-availability.md) | برنامه حضور کارکنان و نظارت مدیر | 03a |
| 03c | [03c-booking-availability.md](03c-booking-availability.md) | موتور زمان آزاد و رزرو امن | 03a و 03b |
| 04 | [04-payments.md](04-payments.md) | پیش‌پرداخت و سوابق پرداخت | 03 |
| 05 | [05-customer-app.md](05-customer-app.md) | تجربهٔ مشتری | 03 و بخش لازم 04 |
| 05a | [05a-customer-booking-flow.md](05a-customer-booking-flow.md) | انتخاب خدمت و متخصص و ثبت نوبت در APP مشتری | 03c و کاتالوگ خدمات 05 |
| 06 | [06-advanced-features.md](06-advanced-features.md) | قابلیت‌های توسعه‌ای انتخاب‌شده | پیش‌نیاز زیرکار منتخب |

فازهای 01 تا 05 بستهٔ کاری اولیه دارند؛ تصمیم‌های باز ابتدای اجرای هر فاز حل و ثبت می‌شوند. فاز 06 مجموعهٔ امکانات آینده است و باید ابتدا به task محدود تبدیل شود.

برای کار جدید از [_template.md](_template.md) کپی بگیر و نامی مانند `07-feature-name.md` انتخاب کن. برای شکستن یک فاز بزرگ از نامی مانند `03a-availability.md` استفاده کن و وابستگی‌ها و وضعیت آن را در همین نقشه و `ProjectStatus.md` ثبت کن. صرف خواندن نقشه مجوز اجرای همهٔ فازها نیست.
