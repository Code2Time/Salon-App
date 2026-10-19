<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductOrder extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['total_amount' => 'integer'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductOrderItem::class);
    }
}
