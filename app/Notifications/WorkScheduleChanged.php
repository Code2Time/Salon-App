<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkScheduleChanged extends Notification
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
            'title' => 'برنامه کاری شما ویرایش شد',
            'message' => "مدیر {$this->salonName} برنامه کاری شما در تاریخ {$this->date} را ویرایش کرد.",
            'date' => $this->date,
            'path' => '/panel/my-schedule',
        ];
    }
}
