<?php

namespace App\Services\Notifications;

class NotificationEmailTransport
{
    public function configured(): bool
    {
        $mailer = config('mail.default');
        $smtp = config("mail.mailers.{$mailer}", []);
        $sender = config('mail.from.address');

        // Log, array and failover-to-log must never count as email delivery.
        if (($smtp['transport'] ?? null) !== 'smtp' || blank($smtp['host'] ?? null)
            || ! filter_var($sender, FILTER_VALIDATE_EMAIL) || $sender === 'hello@example.com'
            || $this->placeholder($sender)) {
            return false;
        }

        if (strtolower($smtp['host']) === 'live.smtp.mailtrap.io') {
            $password = $smtp['password'] ?? null;

            return ($smtp['username'] ?? null) === 'api' && filled($password)
                && $password !== 'null' && ! $this->placeholder($password);
        }

        return true;
    }

    private function placeholder(string $value): bool
    {
        return str_contains(strtoupper($value), 'YOUR_') || str_contains($value, '<') || str_contains($value, '>');
    }
}
