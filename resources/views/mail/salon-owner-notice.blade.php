<!doctype html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><title>{{ $notice['title'] }}</title></head>
<body style="margin:0;padding:24px;background:#faf5f7;font-family:Tahoma,Arial,sans-serif;direction:rtl;text-align:right;line-height:2;color:#35212b">
    <div style="max-width:560px;margin:auto;padding:24px;background:#ffffff;border:1px solid #eadde2;border-radius:16px">
        <p>{{ $notice['salon_name'] }}</p>
        <h1 style="font-size:22px">{{ $notice['title'] }}</h1>
        <p style="white-space:pre-line">{{ $notice['message'] }}</p>
        @if ($panelUrl)
            <p><a href="{{ $panelUrl }}" style="display:inline-block;padding:10px 20px;background:#b21e5b;color:#ffffff;text-decoration:none;border-radius:8px">مشاهده در پنل</a></p>
        @endif
    </div>
</body>
</html>
