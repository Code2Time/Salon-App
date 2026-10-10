<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#faf8f5">
    <title>آرا | فضای زیبایی شما</title>
    <link rel="icon" type="image/png" href="{{ Vite::asset('resources/static/logos/favicon-circle.png') }}">
    <link rel="apple-touch-icon" href="{{ Vite::asset('resources/static/logos/favicon-circle.png') }}">

    @viteReactRefresh
    @vite('resources/js/app.tsx')
</head>

<body>
    <div id="app"></div>
</body>
</html>
