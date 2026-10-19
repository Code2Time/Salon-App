<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

    public function store(Request $request, PlaceProductOrder $service)
    {
        $data = $request->validate([
            'request_id' => ['required', 'uuid'],
            'salon_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*' => ['required', 'array:product_id,quantity,expected_unit_price'],
            'items.*.product_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.expected_unit_price' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ]);

        return (new ProductOrderResource($service->handle($request->user(), $data)))->response()->setStatusCode(201);
    }
}
