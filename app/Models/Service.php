<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'salon_id',
        'name',
        'description',
        'price',
        'duration_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'duration_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCategory::class, 'service_category_service');
    }

    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(SalonUser::class, 'service_salon_user', 'service_id', 'salon_user_id');
    }
}
