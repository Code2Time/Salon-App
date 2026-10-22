<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AftercareRecommendation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['care_instructions' => 'array', 'discount_percent' => 'integer', 'expires_at' => 'datetime', 'redeemed_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function discountIsAvailable(): bool
    {
        return $this->discount_percent > 0 && $this->discount_code && ! $this->redeemed_at && $this->expires_at?->isFuture();
    }
}
