<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ProductOrderPlaced extends Notification
{
    public function __construct(private readonly int $orderId, private readonly string $customerName) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'سفارش محصول جدید',
            'message' => "{$this->customerName} سفارش محصول ثبت کرده است؛ برای هماهنگی تحویل و تسویه، جزئیات سفارش را ببینید.",
            'path' => "/panel/orders/{$this->orderId}",
            'order_id' => $this->orderId,
        ];
    }
}
