<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomerServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'duration_minutes' => $this->duration_minutes,
            'categories' => $this->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'image_url' => $this->imageUrl($category->image),
            ])->values(),
            'providers' => $this->providers
                ->filter(fn ($membership) => $membership->user)
                ->map(fn ($membership) => [
                    'name' => $membership->user->name,
                    'image_url' => $this->imageUrl($membership->user->image),
                ])->values(),
        ];
    }

    private function imageUrl(?string $image): ?string
    {
        if (! $image) {
            return null;
        }

        return Str::startsWith($image, ['http://', 'https://', '/']) ? $image : Storage::url($image);
    }
}
