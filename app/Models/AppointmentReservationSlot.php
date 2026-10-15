<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentReservationSlot extends Model
{
    public $timestamps = false;

    protected $fillable = ['salon_user_id', 'slot_at'];

    protected function casts(): array
    {
        return ['slot_at' => 'datetime'];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
