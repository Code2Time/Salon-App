<?php

namespace App\Http\Resources;

use App\Enums\SalonRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SalonUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrl = $this->image && Str::startsWith($this->image, ['http://', 'https://', '/'])
            ? $this->image
            : ($this->image ? Storage::url($this->image) : null);

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'image_url' => $imageUrl,
            'role' => $this->pivot->role instanceof SalonRole ? $this->pivot->role->value : $this->pivot->role,
            'permissions' => $this->pivot->permissions ?? [],
            'is_active' => (bool) $this->pivot->is_active,
            'created_at' => $this->pivot->created_at?->toISOString(),
        ];
    }
}
