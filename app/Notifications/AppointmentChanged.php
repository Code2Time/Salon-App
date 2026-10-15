<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentChanged extends Notification
{
    use Queueable;

    public function __construct(private readonly string $title, private readonly string $message, private readonly int $appointmentId) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'message' => $this->message, 'path' => '/app/appointments', 'appointment_id' => $this->appointmentId];
    }
}
