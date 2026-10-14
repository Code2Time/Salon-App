<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalonWorkingDay extends Model
{
    protected $fillable = ['date', 'is_open', 'title', 'description'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'is_open' => 'boolean'];
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(SalonWorkingPeriod::class)->orderBy('sort_order')->orderBy('starts_at');
    }
}
