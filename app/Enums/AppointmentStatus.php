<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function blocksTime(): bool
    {
        return ! in_array($this, [self::Cancelled, self::Rejected, self::Expired], true);
    }
}
