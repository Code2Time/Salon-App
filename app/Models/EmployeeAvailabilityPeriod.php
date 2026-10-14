<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAvailabilityPeriod extends Model
{
    protected $fillable = ['starts_at', 'ends_at', 'sort_order'];

    public function day(): BelongsTo
    {
        return $this->belongsTo(EmployeeAvailabilityDay::class, 'employee_availability_day_id');
    }
}
