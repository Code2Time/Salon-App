<?php

namespace App\Enums;

enum SalonRole: string
{
    case Owner = 'salon_owner';
    case Manager = 'manager';
    case Employee = 'employee';

    public function canAccessPanel(): bool
    {
        return true;
    }
}
