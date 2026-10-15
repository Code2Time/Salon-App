<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentItem extends Model
{
    protected $fillable = ['service_id', 'salon_user_id', 'position', 'service_name', 'price', 'duration_minutes', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'duration_minutes' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(SalonUser::class, 'salon_user_id');
    }
}
