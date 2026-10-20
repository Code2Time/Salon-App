<?php

return [
    'enabled' => (bool) env('TELEGRAM_ENABLED', false),
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'queue' => 'telegram',
    'proxy' => env('TELEGRAM_PROXY_URL'),
];
