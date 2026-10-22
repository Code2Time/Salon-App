<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class AftercareProductsSuggested extends Notification
{
    public function __construct(private readonly int $salonId, private readonly int $appointmentId) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['salon_id' => $this->salonId, 'appointment_id' => $this->appointmentId,
            'title' => 'پیشنهاد مراقبت بعد از خدمت',
            'message' => 'محصولات مراقبتی مناسب خدمات شما آماده‌اند؛ پیشنهادها و تخفیف اختصاصی خود را ببینید.',
            'path' => '/app/recommendations'];
    }
}
