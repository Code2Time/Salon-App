<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeAvailabilityDay extends Model
{
    protected $fillable = ['date', 'is_present'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'is_present' => 'boolean'];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(SalonUser::class, 'salon_user_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(EmployeeAvailabilityPeriod::class)->orderBy('sort_order');
    }
}
