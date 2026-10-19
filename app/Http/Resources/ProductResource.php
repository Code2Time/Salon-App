<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'salon_id' => $this->salon_id,
            'name' => $this->name,
            'description' => $this->description ?? '',
            'price' => $this->price,
            'stock' => $this->stock,
            'is_active' => $this->is_active,
            'image_url' => $this->image ? '/storage/'.$this->image : null,
            'recommendations' => $this->whenLoaded('services', fn () => $this->services->map(fn ($service) => [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'recommendation_text' => $service->pivot->recommendation_text ?? '',
                'recommendation_enabled' => (bool) $service->pivot->recommendation_enabled,
                'discount_enabled' => (bool) $service->pivot->discount_enabled,
                'discount_percent' => (int) $service->pivot->discount_percent,
            ])),
        ];
    }
}
