<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductOrderRequest;
use App\Http\Resources\ProductOrderResource;
use App\Models\ProductOrder;
use App\Services\PlaceProductOrder;
use Illuminate\Http\Request;

class CustomerProductOrderController extends Controller
{
    public function index(Request $request)
    {
        return ProductOrderResource::collection(ProductOrder::query()->where('customer_id', $request->user()->id)->with('items')->latest()->paginate(10));
    }

    public function show(Request $request, ProductOrder $order)
    {
        abort_unless($order->customer_id === $request->user()->id, 404);

        return new ProductOrderResource($order->load('items'));
    }

    public function quote(StoreProductOrderRequest $request, PlaceProductOrder $service)
    {
        return response()->json(['data' => $service->quote($request->user(), $request->validated())]);
    }

    public function store(StoreProductOrderRequest $request, PlaceProductOrder $service)
    {
        return (new ProductOrderResource($service->handle($request->user(), $request->validated())))->response()->setStatusCode(201);
    }
}
