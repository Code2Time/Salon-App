<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentSetting extends Model
{
    protected $fillable = ['salon_id', 'planning_period', 'is_recurring', 'timezone'];

    protected function casts(): array
    {
        return ['is_recurring' => 'boolean'];
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }
}
