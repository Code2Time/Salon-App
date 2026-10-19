<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = ['salon_id', 'name', 'description', 'image', 'price', 'stock', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'stock' => 'integer', 'is_active' => 'boolean'];
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withPivot(['recommendation_text', 'recommendation_enabled', 'discount_enabled', 'discount_percent']);
    }
}
