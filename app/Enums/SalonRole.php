<?php

namespace App\Enums;

enum SalonRole: string
{
    case Owner = 'salon_owner';
    case Employee = 'employee';
    case Customer = 'customer';

    public function canAccessPanel(): bool
    {
        return $this !== self::Customer;
    }
}
