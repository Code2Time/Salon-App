<?php

namespace App\Models;

use App\Enums\SalonRole;
use App\Enums\SystemRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $attributes = [
        'is_first_login' => true,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'birth_date',
        'mobile',
        'mobile_verified_at',
        'image',
        'system_role',
        'is_active',
        'is_first_login',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'birth_date' => 'date',
            'system_role' => SystemRole::class,
            'is_active' => 'boolean',
            'is_first_login' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function salons(): BelongsToMany
    {
        return $this->belongsToMany(Salon::class)
            ->withPivot(['id', 'role', 'permissions', 'is_active'])
            ->withTimestamps()
            ->using(SalonUser::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->system_role === SystemRole::SuperAdmin;
    }

    public function canAccessPanel(): bool
    {
        return $this->isSuperAdmin() || $this->salons()
            ->wherePivot('is_active', true)
            ->wherePivotIn('role', [
                SalonRole::Owner->value,
                SalonRole::Employee->value,
            ])
            ->where('salons.is_active', true)
            ->exists();
    }
}
