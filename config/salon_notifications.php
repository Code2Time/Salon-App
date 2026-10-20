<?php

return [
    'email_enabled' => (bool) env('SALON_NOTIFICATION_EMAIL_ENABLED', false),
    'email_queue' => 'mail',
];
