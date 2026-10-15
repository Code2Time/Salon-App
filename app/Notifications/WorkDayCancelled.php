<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkDayCancelled extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $salonId,
        private readonly string $salonName,
        private readonly string $date,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'salon_id' => $this->salonId,
            'title' => 'روز کاری شما لغو شد',
            'message' => "مدیر {$this->salonName} سالن را در تاریخ {$this->date} تعطیل کرد و برنامه حضور شما در این روز لغو شد.",
            'date' => $this->date,
            'path' => '/panel/my-schedule',
        ];
    }
}
