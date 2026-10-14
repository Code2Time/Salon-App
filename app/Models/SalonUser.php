<?php

namespace App\Models;

use App\Enums\SalonRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_salon_user', 'salon_user_id', 'service_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function availabilityDays(): HasMany
    {
        return $this->hasMany(EmployeeAvailabilityDay::class, 'salon_user_id', 'id');
    }
}
