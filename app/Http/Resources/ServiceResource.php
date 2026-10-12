<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'duration_minutes' => $this->duration_minutes,
            'is_active' => $this->is_active,
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'is_active' => $category->is_active,
            ])->values()),
            'providers' => $this->whenLoaded('providers', fn () => $this->providers->map(function ($membership) {
                $image = $membership->user?->image;

                return [
                    'membership_id' => $membership->id,
                    'user_id' => $membership->user_id,
                    'name' => $membership->user?->name,
                    'image_url' => $image && Str::startsWith($image, ['http://', 'https://', '/'])
                        ? $image
                        : ($image ? Storage::url($image) : null),
                ];
            })->values()),
            'categories_count' => $this->whenCounted('categories'),
            'providers_count' => $this->whenCounted('providers'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
