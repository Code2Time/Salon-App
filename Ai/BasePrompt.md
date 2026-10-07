# پروژه SaaS مدیریت سالن زیبایی

## 1. معرفی پروژه

در حال توسعه یک محصول SaaS برای سالن‌های زیبایی زنانه هستیم که هدف آن مدیریت یکپارچه فعالیت‌های سالن و ارائه یک اپلیکیشن برای مشتریان است.

محصول از دو بخش اصلی تشکیل می‌شود:

1. پنل مدیریت سالن (Admin / Management Panel)
2. اپلیکیشن مشتری (Customer App)

هدف اصلی محصول این است که صاحب سالن بتواند بخش عمده‌ای از فرآیندهای روزمره سالن را از طریق پنل مدیریت کند و مشتری نیز بتواند از طریق اپلیکیشن، خدمات سالن را مشاهده و برای دریافت خدمات نوبت رزرو کند.

محصول باید از ابتدا به شکلی طراحی شود که قابلیت تبدیل شدن به یک SaaS چندسالن (Multi-Tenant) را در آینده داشته باشد؛ یعنی بتوان یک سیستم واحد را به چندین سالن ارائه کرد.

---

# 2. تکنولوژی‌های پروژه

## Backend

- Laravel
- PHP
- RESTful API
- Authentication / Authorization
- MySQL یا MariaDB

Laravel مسئول موارد زیر خواهد بود:

- Business Logic
- API
- Authentication
- Authorization
- Database
- مدیریت کاربران
- مدیریت سالن‌ها
- مدیریت خدمات
- مدیریت محصولات
- مدیریت کارکنان
- مدیریت نوبت‌ها
- مدیریت پرداخت‌ها
- مدیریت مشتریان
- Validation
- Notification
- Reporting

Backend باید به صورت API محور طراحی شود تا Frontend وابستگی مستقیمی به Blade یا Viewهای Laravel نداشته باشد.

---

# 3. Frontend

Frontend با تکنولوژی‌های زیر توسعه داده می‌شود:

- React
- TypeScript
- Vite
- Material UI (MUI)

React مسئول UI و تعاملات کاربر است.

TypeScript باید در کل پروژه استفاده شود و تا حد امکان از JavaScript بدون Type استفاده نشود.

Vite مسئول:

- Development Server
- HMR
- Build
- Asset Management

خواهد بود.

---

# 4. UI Framework

فریم‌ورک اصلی UI:

MUI / Material UI

دلایل استفاده:

- وجود Componentهای آماده
- Data Table
- Dialog
- Drawer
- Form Components
- Select
- Autocomplete
- Tabs
- Snackbar
- Pagination
- Responsive Layout
- Date / Time Components
- Accessibility

رابط کاربری باید کاملاً RTL باشد.

زبان اصلی UI فارسی است.

تمام Componentها باید از ابتدا با در نظر گرفتن RTL طراحی شوند.

---

# 5. معماری Frontend

ساختار Frontend باید Modular و قابل توسعه باشد.

پیشنهاد ساختار:

resources/js/

    app.tsx

    App.tsx

    components/
        common/
        forms/
        layout/

    pages/
        dashboard/
        customers/
        employees/
        services/
        products/
        appointments/
        reports/
        settings/

    layouts/
        AdminLayout.tsx

    theme/
        theme.ts

    services/
        api/

    hooks/

    types/

    utils/

    constants/

    routes/

ساختار نهایی می‌تواند در طول توسعه تغییر کند، اما باید از ایجاد یک ساختار بزرگ و غیرقابل مدیریت در یک فایل جلوگیری شود.

---

# 6. Theme

MUI Theme باید به صورت مرکزی مدیریت شود.

Theme باید شامل موارد زیر باشد:

- Colors
- Typography
- Border Radius
- Shadows
- Button Styles
- Input Styles
- Card Styles
- Dialog Styles
- Table Styles
- Breakpoints

رنگ‌بندی اولیه محصول باید حس:

- Beauty
- Premium
- Modern
- Elegant
- Clean

را منتقل کند.

رنگ‌های اولیه می‌توانند شامل:

- Ivory
- Dusty Rose
- Mauve
- Champagne / Gold
- Neutral Gray

باشند.

اما Color Palette نهایی باید در مرحله طراحی UI تعیین شود.

---

# 7. RTL

کل پروژه فارسی و RTL است.

موارد زیر باید RTL باشند:

- Layout
- Sidebar
- Header
- Forms
- Inputs
- Tables
- Dialogs
- Drawer
- Pagination
- Menus
- Dropdowns
- Notifications
- Icons
- Calendar

نباید صرفاً `dir="rtl"` روی HTML قرار داده شود و انتظار داشته باشیم تمام Componentها به صورت صحیح RTL شوند.

MUI Emotion RTL configuration باید به صورت صحیح پیاده‌سازی شود.

---

# 8. صفحات اصلی پنل مدیریت

پنل مدیریت سالن باید حداقل شامل بخش‌های زیر باشد.

## Dashboard

داشبورد باید اطلاعات مهم سالن را در یک نگاه نمایش دهد.

موارد احتمالی:

- تعداد نوبت‌های امروز
- نوبت‌های آینده
- تعداد مشتریان
- درآمد امروز
- درآمد ماه جاری
- خدمات محبوب
- کارکنان
- نوبت‌های لغوشده
- نوبت‌های در انتظار
- نمودار درآمد
- نمودار تعداد نوبت‌ها

Dashboard باید قابل توسعه باشد.

---

# 9. مدیریت مشتریان

مدیر سالن باید بتواند مشتریان را مدیریت کند.

اطلاعات احتمالی مشتری:

- نام
- نام خانوادگی
- شماره موبایل
- تاریخ تولد
- توضیحات
- تاریخ اولین مراجعه
- آخرین مراجعه
- تعداد مراجعه
- مجموع پرداختی
- خدمات دریافت‌شده

امکانات:

- ایجاد مشتری
- ویرایش مشتری
- حذف / غیرفعال کردن
- جستجو
- فیلتر
- مشاهده پروفایل مشتری
- مشاهده تاریخچه نوبت‌ها
- مشاهده تاریخچه خدمات

---

# 10. مدیریت کارکنان

مدیر سالن باید بتواند کارکنان را مدیریت کند.

اطلاعات:

- نام
- شماره موبایل
- تخصص
- خدمات قابل ارائه
- ساعت کاری
- روزهای کاری
- وضعیت فعال / غیرفعال

هر کارمند می‌تواند خدمات مشخصی را ارائه دهد.

برای مثال:

کارمند A:

- کوتاهی
- رنگ مو
- براشینگ

کارمند B:

- ناخن
- مانیکور
- پدیکور

---

# 11. مدیریت خدمات

مدیر سالن باید بتواند خدمات را ایجاد و مدیریت کند.

هر Service حداقل شامل:

- نام
- دسته‌بندی
- توضیحات
- قیمت
- مدت زمان
- وضعیت فعال / غیرفعال
- کارکنان ارائه‌دهنده

باشد.

مثال:

دسته:

مو

خدمات:

- کوتاهی مو
- رنگ مو
- دکلره
- براشینگ

هر خدمت می‌تواند مدت زمان متفاوتی داشته باشد.

مثلاً:

کوتاهی:
60 دقیقه

رنگ:
120 دقیقه

دکلره:
180 دقیقه

سیستم رزرو باید مدت زمان Service را در محاسبه زمان آزاد لحاظ کند.

---

# 12. مدیریت محصولات

سالن می‌تواند محصولاتی را برای فروش تعریف کند.

مثال:

- شامپو
- ماسک مو
- سرم مو
- محصولات مراقبت پوست

محصول می‌تواند در اپلیکیشن مشتری نمایش داده شود.

اطلاعات محصول:

- نام
- تصویر
- قیمت
- توضیحات
- موجودی
- وضعیت فعال / غیرفعال

در آینده می‌توان امکان خرید آنلاین محصولات را نیز اضافه کرد.

---

# 13. دسته‌بندی خدمات

Serviceها باید قابلیت دسته‌بندی داشته باشند.

مثال:

مو

- کوتاهی
- رنگ
- دکلره
- براشینگ

ناخن

- مانیکور
- پدیکور
- ژل

پوست

- فیشال
- پاکسازی
- ماساژ

دسته‌بندی‌ها باید Dynamic باشند و مدیر بتواند آنها را مدیریت کند.

---

# 14. سیستم نوبت‌دهی

یکی از مهم‌ترین بخش‌های سیستم Appointment است.

مشتری باید بتواند:

1. سالن را انتخاب کند
2. دسته‌بندی را انتخاب کند
3. خدمت را انتخاب کند
4. در صورت نیاز کارمند را انتخاب کند
5. تاریخ را انتخاب کند
6. زمان آزاد را مشاهده کند
7. نوبت را رزرو کند
8. در صورت نیاز پیش‌پرداخت انجام دهد

---

# 15. محاسبه زمان آزاد

سیستم نباید صرفاً بر اساس ساعت‌های ثابت کار کند.

مدت زمان Service باید در محاسبه Availability لحاظ شود.

مثال:

اگر یک کارمند از ساعت 10 تا 18 کار می‌کند:

Service A:
60 دقیقه

Service B:
120 دقیقه

اگر ساعت 14 تا 15 رزرو شده باشد، سیستم باید بتواند تشخیص دهد که آیا یک سرویس 120 دقیقه‌ای در بازه‌های باقی‌مانده قابل رزرو است یا خیر.

---

# 16. قوانین رزرو

رزرو می‌تواند شامل قوانین مختلف باشد.

مثلاً:

- حداقل فاصله تا زمان رزرو
- حداکثر مدت زمان قابل رزرو در آینده
- حداقل زمان لغو
- نیاز به پیش‌پرداخت
- ظرفیت کارکنان
- روزهای تعطیل
- ساعت کاری
- زمان استراحت کارکنان

این قوانین باید تا حد امکان Configurable باشند.

---

# 17. لغو نوبت

مدیر سالن باید بتواند قانون لغو تعریف کند.

مثلاً:

اگر کمتر از 24 ساعت تا زمان نوبت باقی مانده باشد، مشتری اجازه لغو نداشته باشد.

یا:

لغو فقط با تأیید مدیر انجام شود.

این مقدار نباید Hard Code شود و در آینده باید قابلیت تنظیم داشته باشد.

---

# 18. پیش‌پرداخت

برای برخی خدمات امکان دریافت Deposit وجود داشته باشد.

مثلاً:

خدمت:
رنگ مو

قیمت:
2,000,000

پیش‌پرداخت:
500,000

در هنگام رزرو مشتری باید مبلغ Deposit را پرداخت کند.

سیستم باید وضعیت پرداخت را ذخیره کند.

---

# 19. وضعیت نوبت

Appointment می‌تواند وضعیت‌های مختلف داشته باشد.

مثلاً:

- Pending
- Confirmed
- Completed
- Cancelled
- No Show
- Rejected

و این Statusها باید در UI به شکل واضح نمایش داده شوند.

---

# 20. اپلیکیشن مشتری

Customer App باید تجربه‌ای ساده و مدرن داشته باشد.

صفحات احتمالی:

- Home
- Services
- Service Details
- Employees
- Booking
- Calendar
- My Appointments
- Appointment Details
- Profile
- Notifications
- Products

---

# 21. صفحه Home مشتری

Home باید اطلاعات مهم را نمایش دهد:

- معرفی سالن
- خدمات محبوب
- پیشنهادها
- تخفیف‌ها
- آخرین خدمات
- محصولات
- دکمه رزرو سریع

---

# 22. احراز هویت

احراز هویت باید API Based باشد.

مشتری می‌تواند با شماره موبایل Login کند.

احتمال استفاده از:

- OTP
- Token
- Sanctum

وجود دارد.

معماری Authentication باید به شکلی باشد که بعداً امکان تغییر Provider وجود داشته باشد.

---

# 23. Authorization

سیستم باید Role Based Access Control داشته باشد.

مثلاً:

Super Admin

Salon Owner

Manager

Employee

Customer

هر Role باید دسترسی مشخص خود را داشته باشد.

---

# 24. API Architecture

Backend باید API محور باشد.

مثال:

GET /api/services

POST /api/services

GET /api/appointments

POST /api/appointments

PUT /api/appointments/{id}

DELETE /api/appointments/{id}

APIها باید:

- Validation
- Authentication
- Authorization
- Error Handling
- Consistent Response Structure

داشته باشند.

---

# 25. مدیریت State در React

برای Stateهای مختلف نباید همه چیز داخل Componentها نگهداری شود.

برای Server State می‌توان از:

React Query / TanStack Query

استفاده کرد.

برای Form:

React Hook Form

می‌تواند استفاده شود.

برای Global Client State در صورت نیاز می‌توان از:

Zustand

یا راهکار مشابه استفاده کرد.

اما نباید بدون نیاز واقعی State Management پیچیده ایجاد شود.

---

# 26. Form Management

Formهای پیچیده باید قابل مدیریت و Validation باشند.

مواردی مثل:

- Service Form
- Employee Form
- Customer Form
- Appointment Form
- Product Form

بهتر است با React Hook Form پیاده‌سازی شوند.

Validation می‌تواند با Zod یا راهکار مشابه انجام شود.

---

# 27. Error Handling

تمام درخواست‌های API باید Error Handling مناسب داشته باشند.

UI باید خطاها را به شکل قابل فهم نمایش دهد.

مثلاً:

- خطای اعتبارسنجی
- خطای احراز هویت
- خطای دسترسی
- خطای Server
- خطای Network

نباید Errorهای خام Backend مستقیماً به کاربر نمایش داده شوند.

---

# 28. Loading State

تمام عملیات Async باید Loading State داشته باشند.

مثلاً:

- Loading Table
- Loading Button
- Loading Page
- Skeleton
- Loading Dialog

کاربر نباید در هنگام ارسال Request تصور کند سیستم کار نمی‌کند.

---

# 29. Responsive Design

پنل باید Responsive باشد.

حداقل:

- Desktop
- Tablet
- Mobile

را پشتیبانی کند.

با این حال، پنل مدیریت Desktop First خواهد بود و Customer App باید Mobile First طراحی شود.

---

# 30. امنیت

امنیت Backend اولویت بالایی دارد.

موارد مهم:

- Authentication
- Authorization
- Request Validation
- Rate Limiting
- CSRF در صورت نیاز
- SQL Injection Protection
- XSS Protection
- Secure API Design
- Password Hashing
- Secure Token Handling

نباید اطلاعات حساس در Frontend قرار بگیرد.

---

# 31. Database

Database باید نرمال و قابل توسعه طراحی شود.

جداول احتمالی:

users

salons

employees

customers

services

service_categories

products

product_categories

appointments

appointment_items

payments

working_hours

days_off

notifications

roles

permissions

و جداول مورد نیاز دیگر.

ساختار نهایی Database باید بر اساس نیاز واقعی سیستم تعیین شود.

---

# 32. Multi-Tenant Architecture

اگرچه نسخه اولیه ممکن است برای یک سالن توسعه داده شود، Backend و Database باید طوری طراحی شوند که در آینده بتوان چند سالن را پشتیبانی کرد.

در آینده:

Salon A

Salon B

Salon C

باید بتوانند از یک سیستم استفاده کنند بدون اینکه داده‌های آنها با یکدیگر تداخل داشته باشد.

بنابراین از ابتدا باید Tenant / Salon Context در معماری در نظر گرفته شود.

اما نباید در نسخه MVP بیش از حد پیچیده شود.

---

# 33. Logging و Monitoring

سیستم باید قابلیت Logging مناسب داشته باشد.

خطاهای مهم Backend باید قابل بررسی باشند.

در آینده می‌توان ابزارهایی مانند:

- Laravel Telescope
- Sentry
- سایر Monitoring Tools

را اضافه کرد.

---

# 34. Git

پروژه باید با Git مدیریت شود.

Commitها باید معنی‌دار باشند.

مثلاً:

feat: add appointment management

fix: resolve appointment availability issue

refactor: improve service form

chore: update dependencies

---

# 35. Development Rules

در توسعه پروژه:

- از Code Duplication جلوگیری شود.
- Componentهای قابل استفاده مجدد ساخته شوند.
- Logic پیچیده داخل UI قرار نگیرد.
- Business Logic در Backend مدیریت شود.
- TypeScript به صورت واقعی استفاده شود.
- از `any` تا حد امکان استفاده نشود.
- Componentهای بسیار بزرگ شکسته شوند.
- Naming Convention یکسان باشد.
- API Layer از UI جدا باشد.
- Constants در فایل‌های مناسب نگهداری شوند.
- Magic Number و Magic String کاهش پیدا کند.

---

# 36. اولویت توسعه

پروژه نباید از ابتدا با تمام قابلیت‌ها ساخته شود.

اولویت پیشنهادی:

### Phase 1 — Foundation

- Laravel
- React
- TypeScript
- Vite
- MUI
- RTL
- Theme
- API Architecture
- Authentication

### Phase 2 — Salon Management

- Dashboard
- Customers
- Employees
- Services
- Categories
- Working Hours

### Phase 3 — Appointment

- Booking
- Availability
- Calendar
- Appointment Management
- Cancellation Rules

### Phase 4 — Payments

- Deposit
- Payment
- Payment Status
- Payment History

### Phase 5 — Customer App

- Home
- Services
- Booking
- Appointments
- Profile
- Notifications

### Phase 6 — Advanced Features

- Products
- Reports
- Discounts
- Notifications
- Analytics
- Multi-Tenant Improvements

---

# 37. اصل مهم در توسعه

قبل از پیاده‌سازی هر Feature ابتدا:

1. نیازمندی Feature مشخص شود.
2. Data Model بررسی شود.
3. API Contract مشخص شود.
4. UI Flow مشخص شود.
5. سپس Backend و Frontend پیاده‌سازی شوند.

از پیاده‌سازی سریع Feature بدون در نظر گرفتن معماری کلی خودداری شود.

---

# 38. وضعیت فعلی پروژه

در حال حاضر پروژه در مرحله Foundation قرار دارد.

تکنولوژی‌های اصلی:

Laravel
React
TypeScript
Vite
MUI

پروژه باید به صورت مرحله‌ای توسعه داده شود.

در حال حاضر تمرکز روی ایجاد Architecture و UI Foundation است و نباید بدون مشخص شدن نیازمندی‌های Feature، قابلیت‌های پیچیده به پروژه اضافه شود.

---

# 39. نکته بسیار مهم درباره Requirements

این سند، **نسخه اولیه نیازمندی‌های پروژه** است و نباید به عنوان Specification نهایی و غیرقابل تغییر در نظر گرفته شود.

در طول توسعه، ممکن است بر اساس:

- Feedback صاحبان سالن
- تست با کاربران واقعی
- تحقیقات بازار
- مشکلات فنی
- تجربه کاربری
- مدل کسب‌وکار
- محدودیت‌های عملیاتی
- نیازهای جدید مشتریان

نیازمندی‌ها تغییر کنند.

بنابراین Architecture باید تا حد امکان:

- Modular
- Maintainable
- Extensible
- Loosely Coupled

باشد تا تغییر Requirements در آینده کمترین هزینه را داشته باشد.

در صورت تغییر نیازمندی، قبل از پیاده‌سازی باید تأثیر آن روی:

- Database
- API
- Backend
- Frontend
- UI
- Business Logic

بررسی شود.

از Hard Code کردن فرضیات فعلی خودداری شود.

---

# 40. هدف نهایی

هدف نهایی ساخت یک SaaS حرفه‌ای، مدرن و قابل توسعه برای مدیریت سالن‌های زیبایی است که بتواند از یک MVP ساده شروع شود و در آینده به یک محصول تجاری کامل با قابلیت پشتیبانی از تعداد زیادی سالن تبدیل شود.

تمرکز اصلی در تمام مراحل:

**Simplicity + Maintainability + Scalability + UX**

است.

فایل Ai\BasePrompt.md رو مطالع کن.من دقیقا همه چیز رو برای تو توی این فایل قرار دادم.میخوام تو پوشه Ai فایل هایی داشته باشیم که من اول بعدا بهت بگم فایل معرفی رو بخون و در پوشه دیگه ای به عنوان task بهت بگم فاز بعدی فلان file هست مطالعه و پیاده سازی کن.این ساختار رو پیاده سازی کن و یک نمونه پرامپ شروع بهم بده که بعدا ازش استفاده کنم
