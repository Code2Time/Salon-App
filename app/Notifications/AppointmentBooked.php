<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class AppointmentBooked extends Notification
{
    public function __construct(private readonly int $salonId, private readonly int $appointmentId, private readonly string $message) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['salon_id' => $this->salonId, 'appointment_id' => $this->appointmentId,
            'title' => 'نوبت جدید', 'message' => $this->message, 'path' => '/panel/appointments'];
    }
}
