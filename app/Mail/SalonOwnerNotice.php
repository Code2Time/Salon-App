<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SalonOwnerNotice extends Mailable
{
    public function __construct(public array $notice) {}

    public function envelope(): Envelope
    {
        $subject = preg_replace('/[\r\n]+/', ' ', $this->notice['salon_name'].' — '.$this->notice['title']);

        return new Envelope(subject: mb_substr($subject, 0, 200));
    }

    public function content(): Content
    {
        $path = $this->notice['path'] ?? '';
        $base = rtrim(config('app.url'), '/');
        $url = preg_match('~^https?://~', $base) && str_starts_with($path, '/panel/') ? $base.$path : null;

        return new Content(view: 'mail.salon-owner-notice', text: 'mail.salon-owner-notice-text', with: ['panelUrl' => $url]);
    }
}
