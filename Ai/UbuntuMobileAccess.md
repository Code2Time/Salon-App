# اجرای پروژه روی Ubuntu و بازکردن آن با گوشی

این راهنما دو روش را پوشش می‌دهد:

1. اتصال گوشی و Ubuntu به یک Wi-Fi و بازکردن برنامه از IP محلی؛
2. ساخت لینک عمومی موقت با Cloudflare Quick Tunnel.

روش شبکه محلی برای توسعه سریع‌تر و امن‌تر است. Quick Tunnel فقط برای تست و نمایش موقت مناسب است.

## ۱. آماده‌سازی پروژه

پس از نصب PHP، Composer، Node.js، MySQL/MariaDB و افزونه‌های لازم PHP، از ریشه پروژه اجرا کن:

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

اطلاعات دیتابیس را در `.env` تنظیم کن و سپس:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan optimize:clear
```

برای اتصال گوشی از خروجی Build استفاده کن. اگر قبلاً `npm run dev` اجرا شده و فایل `public/hot` باقی مانده است، آن را حذف کن تا صفحه گوشی به `localhost` یا پورت Vite روی خود گوشی هدایت نشود:

```bash
rm -f public/hot
```

## ۲. اتصال گوشی در شبکه محلی

IP سیستم Ubuntu را پیدا کن:

```bash
hostname -I
```

اولین IP شبکه، مثلاً `192.168.1.25`، را بردار. در `.env` همان IP و پورت را به دامنه‌های Sanctum اضافه کن:

```dotenv
SANCTUM_STATEFUL_DOMAINS=localhost:8000,127.0.0.1:8000,192.168.1.25:8000,__SANCTUM_CURRENT_REQUEST_HOST__
```

بعد از تغییر `.env`:

```bash
php artisan optimize:clear
php artisan serve --host=0.0.0.0 --port=8000
```

در ترمینال دیگری Scheduler را روشن نگه دار تا وضعیت نوبت‌های گذشته به‌روز شود:

```bash
php artisan schedule:work
```

اگر UFW فعال است، پورت توسعه را فقط برای شبکه محلی خود باز کن. Subnet را متناسب با شبکه خودت تغییر بده:

```bash
sudo ufw allow from 192.168.1.0/24 to any port 8000 proto tcp
```

حالا گوشی و Ubuntu باید به یک Wi-Fi متصل باشند. در مرورگر گوشی باز کن:

```text
http://192.168.1.25:8000
```

اگر صفحه باز نشد، به‌ترتیب IP، یکسان‌بودن شبکه، Firewall و فعال‌بودن `artisan serve` را بررسی کن. اگر صفحه باز شد ولی Login خطای `Session store not set on request` داد، مقدار `SANCTUM_STATEFUL_DOMAINS` را کنترل و `php artisan optimize:clear` را دوباره اجرا کن.

## ۳. نصب cloudflared روی Ubuntu

دستورات رسمی مخزن APT کلادفلر:

```bash
sudo mkdir -p --mode=0755 /usr/share/keyrings
curl -fsSL https://pkg.cloudflare.com/cloudflare-main.gpg \
  | sudo tee /usr/share/keyrings/cloudflare-main.gpg >/dev/null

echo "deb [signed-by=/usr/share/keyrings/cloudflare-main.gpg] https://pkg.cloudflare.com/cloudflared any main" \
  | sudo tee /etc/apt/sources.list.d/cloudflared.list

sudo apt-get update
sudo apt-get install cloudflared
cloudflared --version
```

مرجع: [نصب cloudflared روی Debian و Ubuntu](https://developers.cloudflare.com/tunnel/features/locally-managed-tunnels/create-local-tunnel/)

## ۴. ساخت لینک عمومی موقت

سرور Laravel را روشن نگه دار و در ترمینال جدید اجرا کن:

```bash
cloudflared tunnel --url http://127.0.0.1:8000
```

آدرس تصادفی `https://....trycloudflare.com` در خروجی نمایش داده می‌شود. این آدرس با توقف فرایند `cloudflared` از کار می‌افتد و در اجرای بعدی تغییر می‌کند.

برای محدودکردن دسترسی به ایمیل خودت:

```bash
cloudflared tunnel --url http://127.0.0.1:8000 \
  --allowed-mail 'you@example.com'
```

مرجع: [Cloudflare Quick Tunnels](https://developers.cloudflare.com/tunnel/get-started/quick-tunnels/)

## ۵. نکات امنیتی مهم

- Quick Tunnel مخصوص توسعه است و تضمین پایداری ندارد.
- هر فردی که لینک بدون محدودیت را داشته باشد می‌تواند برنامه را ببیند.
- این پروژه در محیط توسعه OTP ثابت `1111` و حساب‌های Seed دارد؛ دیتابیس واقعی یا Seed را با Tunnel عمومی بدون `--allowed-mail` منتشر نکن.
- مقدار `__SANCTUM_CURRENT_REQUEST_HOST__` فقط برای توسعه و دامنه‌های موقت است. در Production آن را حذف و دامنه واقعی را صریحاً در `SANCTUM_STATEFUL_DOMAINS` ثبت کن.
- برای آدرس ثابت و استفاده دائمی، به‌جای Quick Tunnel یک Tunnel مدیریت‌شده با دامنه خودت بساز: [راه‌اندازی Cloudflare Tunnel](https://developers.cloudflare.com/tunnel/get-started/).

## ۶. رفع خطاهای رایج Tunnel

وضعیت سرویس محلی را ابتدا بررسی کن:

```bash
curl -I http://127.0.0.1:8000
```

اگر `cloudflared` خطای DNS برای `argotunnel.com` یا `cfargotunnel.com` داد، DNS سیستم/مودم و دسترسی خروجی شبکه را بررسی کن. در شبکه‌های محدود، Cloudflare توصیه می‌کند اتصال خروجی موردنیاز Tunnel، از جمله پورت `7844`، مسدود نباشد. Quick Tunnel به بازکردن پورت ورودی Router نیاز ندارد.

اگر ظاهر قدیمی نمایش داده شد:

```bash
npm run build
rm -f public/hot
php artisan optimize:clear
```

سپس Cache صفحه را در مرورگر گوشی پاک یا Hard Refresh کن.
