<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'salon_id' => $this->salon_id,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'total_amount' => $this->total_amount,
            'subtotal_amount' => $this->subtotal_amount, 'discount_amount' => $this->discount_amount, 'discount_code' => $this->discount_code,
            'customer_name' => $this->customer_name,
            'customer_mobile' => $this->customer_mobile,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toISOString(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id, 'product_id' => $item->product_id, 'product_name' => $item->product_name,
                'unit_price' => $item->unit_price, 'quantity' => $item->quantity, 'total_amount' => $item->total_amount,
                'discount_amount' => $item->discount_amount, 'discount_percent' => $item->discount_percent,
                'delivery_status' => $item->delivery_status,
                'delivered_at' => $item->delivered_at?->toISOString(),
                'payment_status' => $item->payment_status,
                'payment_method' => $item->payment_method,
                'amount_received' => $item->amount_received,
                'receipt_url' => $item->receipt_path && $request->is('api/panel/*') ? url("/api/panel/orders/items/{$item->id}/receipt") : null,
            ])),
        ];
    }
}
