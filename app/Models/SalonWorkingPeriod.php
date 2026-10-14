<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalonWorkingPeriod extends Model
{
    protected $fillable = ['starts_at', 'ends_at', 'sort_order'];

    public function workingDay(): BelongsTo
    {
        return $this->belongsTo(SalonWorkingDay::class, 'salon_working_day_id');
    }
}
