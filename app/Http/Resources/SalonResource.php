<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'aftercare_discount_validity_days' => $this->when($request->is('api/panel/*'), $this->aftercare_discount_validity_days),
            'logo_url' => $this->logo ? '/storage/'.$this->logo : null,
            'description' => $this->description,
            'phone' => $this->phone,
            'address' => $this->address,
            'instagram_url' => $this->instagram_url,
            'telegram_url' => $this->telegram_url,
            'whatsapp_url' => $this->whatsapp_url,
            'website_url' => $this->website_url,
        ];
    }
}
