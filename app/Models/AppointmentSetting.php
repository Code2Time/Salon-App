<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentSetting extends Model
{
    protected $fillable = ['salon_id', 'planning_period', 'is_recurring', 'timezone', 'slot_interval_minutes', 'cancellation_notice_hours', 'deposit_enabled', 'deposit_type', 'deposit_value'];

    protected function casts(): array
    {
        return ['is_recurring' => 'boolean', 'deposit_enabled' => 'boolean', 'slot_interval_minutes' => 'integer', 'cancellation_notice_hours' => 'integer', 'deposit_value' => 'integer'];
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }
}
