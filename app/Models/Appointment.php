<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    public function scopeVisibleTo(Builder $query, User $user, int $salonId): Builder
    {
        $query->where('appointments.salon_id', $salonId);

        if (! $user->canViewAllAppointmentsIn($salonId)) {
            $query->whereHas('items.provider', fn ($provider) => $provider
                ->where('salon_id', $salonId)
                ->where('user_id', $user->id)
                ->where('role', 'employee')
                ->where('is_active', true));
        }

        return $query;
    }

    protected $fillable = ['customer_id', 'created_by_id', 'source', 'status', 'starts_at', 'ends_at', 'total_price', 'required_deposit_amount', 'notes', 'cancellation_reason', 'cancelled_by_id', 'cancelled_at'];

    protected function casts(): array
    {
        return ['status' => AppointmentStatus::class, 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'cancelled_at' => 'datetime', 'total_price' => 'integer', 'required_deposit_amount' => 'integer'];
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AppointmentItem::class)->orderBy('position');
    }

    public function reservationSlots(): HasMany
    {
        return $this->hasMany(AppointmentReservationSlot::class);
    }
}
