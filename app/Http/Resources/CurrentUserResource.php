<?php

namespace App\Http\Resources;

use App\Enums\SalonRole;
use App\Services\Otp\IranianMobile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CurrentUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $memberships = $this->salons->map(fn ($salon) => [
            'salon_id' => $salon->id,
            'salon_name' => $salon->name,
            'role' => $salon->pivot->role instanceof SalonRole
                ? $salon->pivot->role->value
                : $salon->pivot->role,
            'permissions' => $salon->pivot->permissions ?? [],
        ])->values();
        $panelAccess = $this->isSuperAdmin() || $memberships->contains(
            fn (array $membership) => $membership['role'] !== SalonRole::Customer->value,
        );
        $imageUrl = $this->image && Str::startsWith($this->image, ['http://', 'https://', '/'])
            ? $this->image
            : ($this->image ? Storage::url($this->image) : null);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'email' => $this->email,
            'mobile' => IranianMobile::display($this->mobile),
            'image_url' => $imageUrl,
            'system_role' => $this->system_role?->value,
            'memberships' => $memberships,
            'abilities' => [
                'access_panel' => $panelAccess,
                'access_customer_app' => true,
            ],
            'home_path' => $panelAccess ? '/panel' : '/app',
            'profile_completed' => filled($this->first_name) && filled($this->last_name),
        ];
    }
}
