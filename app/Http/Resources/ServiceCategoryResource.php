<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrl = $this->image && Str::startsWith($this->image, ['http://', 'https://', '/'])
            ? $this->image
            : ($this->image ? Storage::url($this->image) : null);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $imageUrl,
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'services_count' => $this->whenCounted('services'),
            'services' => $this->whenLoaded('services', fn () => $this->services->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'price' => $service->price,
                'duration_minutes' => $service->duration_minutes,
                'is_active' => $service->is_active,
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
