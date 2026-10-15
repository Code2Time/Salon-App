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

    /**
     * Return permissions as a normalized list, including legacy double-encoded values.
     *
     * @return list<string>
     */
    public function permissionNames(): array
    {
        $permissions = $this->permissions;

        for ($attempt = 0; $attempt < 2 && is_string($permissions); $attempt++) {
            $permissions = json_decode($permissions, true);
        }

        return is_array($permissions)
            ? array_values(array_filter($permissions, 'is_string'))
            : [];
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
