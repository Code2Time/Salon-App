<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOrderItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'integer', 'quantity' => 'integer', 'total_amount' => 'integer',
            'amount_received' => 'integer', 'delivered_at' => 'datetime',
        ];
    }

    public function productOrder(): BelongsTo
    {
        return $this->belongsTo(ProductOrder::class);
    }
}
