<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AftercareRecommendationResource;
use App\Models\AftercareRecommendation;
use Illuminate\Http\Request;

class AftercareRecommendationController extends Controller
{
    public function index(Request $request)
    {
        $query = AftercareRecommendation::query()->where('customer_id', $request->user()->id)
            ->whereHas('salon', fn ($salon) => $salon->where('is_active', true))
            ->whereHas('product', fn ($product) => $product->where('is_active', true)->where('stock', '>', 0))
            ->with(['product', 'salon'])->latest('id');

        return AftercareRecommendationResource::collection($query->paginate(12));
    }
}
