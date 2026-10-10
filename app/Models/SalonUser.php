<?php

namespace App\Models;

use App\Enums\SalonRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SalonUser extends Pivot
{
    protected $table = 'salon_user';

    protected function casts(): array
    {
        return [
            'role' => SalonRole::class,
            'permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
