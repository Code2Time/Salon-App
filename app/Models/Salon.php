<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Salon extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_active', 'logo', 'description', 'phone', 'address', 'instagram_url', 'telegram_url', 'whatsapp_url', 'website_url', 'aftercare_discount_validity_days'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'aftercare_discount_validity_days' => 'integer'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['id', 'role', 'permissions', 'is_active', 'telegram_chat_id', 'notification_email'])
            ->withTimestamps()
            ->using(SalonUser::class);
    }

    public function serviceCategories(): HasMany
    {
        return $this->hasMany(ServiceCategory::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function appointmentSetting(): HasOne
    {
        return $this->hasOne(AppointmentSetting::class);
    }

    public function workingDays(): HasMany
    {
        return $this->hasMany(SalonWorkingDay::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
