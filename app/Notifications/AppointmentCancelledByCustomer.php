<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentCancelledByCustomer extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $appointmentId,
        private readonly string $customerName,
        private readonly ?string $reason,
        private readonly int $salonId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $message = "{$this->customerName} نوبت خود را لغو کرد.";

        if (filled($this->reason)) {
            $message .= " دلیل: {$this->reason}";
        }

        return [
            'salon_id' => $this->salonId,
            'title' => 'لغو نوبت توسط مشتری',
            'message' => $message,
            'path' => '/panel/appointments',
            'appointment_id' => $this->appointmentId,
        ];
    }
}
