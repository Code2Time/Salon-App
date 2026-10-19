<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Salon;
use Illuminate\Http\Request;

class CustomerProductController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['salon' => ['nullable', 'string', 'max:100'], 'search' => ['nullable', 'string', 'max:150']]);
        $salon = Salon::query()->where('is_active', true)
            ->when($data['salon'] ?? null, fn ($query, $slug) => $query->where('slug', $slug))
            ->orderByRaw("CASE WHEN slug = 'ara' THEN 0 ELSE 1 END")->orderBy('id')->firstOrFail();
        $query = Product::query()->where('salon_id', $salon->id)->where('is_active', true)->latest();
        if ($search = $data['search'] ?? null) {
            $query->where('name', 'like', "%{$search}%");
        }

        return ProductResource::collection($query->paginate(12))->additional(['salon' => ['id' => $salon->id, 'name' => $salon->name]]);
    }
}
