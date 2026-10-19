<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductOrderResource;
use App\Models\ProductOrder;
use App\Models\ProductOrderItem;
use App\Services\CurrentSalon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductOrderController extends Controller
{
    public function index(Request $request, CurrentSalon $context)
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:150']]);
        $query = ProductOrder::query()->where('salon_id', $context->get()->id)->with('items')->latest();
        if ($search = $data['search'] ?? null) {
            $query->where(fn ($sub) => $sub->where('customer_name', 'like', "%{$search}%")->orWhere('customer_mobile', 'like', "%{$search}%"));
        }

        return ProductOrderResource::collection($query->paginate(10));
    }

    public function show(ProductOrder $order, CurrentSalon $context)
    {
        abort_unless($order->salon_id === $context->get()->id, 404);

        return new ProductOrderResource($order->load('items'));
    }

    public function updateItem(Request $request, int $item, CurrentSalon $context)
    {
        $line = ProductOrderItem::query()->with('productOrder')->findOrFail($item);
        abort_unless($line->productOrder->salon_id === $context->get()->id, 404);

        $data = $request->validate([
            'delivery_status' => ['required', 'in:pending,delivered'],
            'payment_status' => ['required', 'in:unpaid,partial,paid'],
            'payment_method' => ['nullable', 'in:cash,card,transfer,other'],
            'amount_received' => ['required', 'integer', 'min:0', 'max:'.$line->total_amount],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'remove_receipt' => ['nullable', 'boolean'],
        ]);

        $isDelivered = $data['delivery_status'] === 'delivered';
        $isPaid = in_array($data['payment_status'], ['partial', 'paid'], true);
        $amountReceived = (int) $data['amount_received'];
        if (! $isDelivered && ($isPaid || $amountReceived !== 0 || ! empty($data['payment_method']) || $request->hasFile('receipt'))) {
            return response()->json(['message' => 'برای ثبت دریافت وجه، ابتدا وضعیت محصول را تحویل‌شده ثبت کنید.'], 422);
        }
        if ($isPaid && empty($data['payment_method'])) {
            return response()->json(['message' => 'روش دریافت وجه را انتخاب کنید.'], 422);
        }
        if ($data['payment_status'] === 'unpaid' && ($amountReceived !== 0 || ! empty($data['payment_method']) || $request->hasFile('receipt'))) {
            return response()->json(['message' => 'برای سفارش پرداخت‌نشده، مبلغ و روش دریافت ثبت نکنید.'], 422);
        }
        if ($data['payment_status'] === 'paid' && $amountReceived !== $line->total_amount) {
            return response()->json(['message' => 'مبلغ دریافت‌شده باید با مبلغ این محصول برابر باشد.'], 422);
        }
        if ($data['payment_status'] === 'partial' && ($amountReceived < 1 || $amountReceived >= $line->total_amount)) {
            return response()->json(['message' => 'برای دریافت بخشی از مبلغ، عددی کمتر از مبلغ محصول وارد کنید.'], 422);
        }

        $previousReceipt = $line->receipt_path;
        $newReceipt = null;
        if ($request->hasFile('receipt')) {
            $extension = $request->file('receipt')->guessExtension() ?: 'bin';
            $newReceipt = $request->file('receipt')->storeAs(
                "product-order-receipts/{$line->productOrder->salon_id}/{$line->product_order_id}",
                Str::uuid().'.'.$extension,
                'local'
            );
        }

        try {
            $line->forceFill([
                'delivery_status' => $data['delivery_status'],
                'delivered_at' => $isDelivered ? ($line->delivered_at ?? now()) : null,
                'payment_status' => $data['payment_status'],
                'payment_method' => $isPaid ? $data['payment_method'] : null,
                'amount_received' => $amountReceived,
                'receipt_path' => $isDelivered && $isPaid
                    ? ($newReceipt ?? (! ($data['remove_receipt'] ?? false) ? $previousReceipt : null))
                    : null,
            ])->save();
        } catch (\Throwable $exception) {
            if ($newReceipt) {
                Storage::disk('local')->delete($newReceipt);
            }
            throw $exception;
        }

        $currentReceipt = $line->receipt_path;
        if ($previousReceipt && $previousReceipt !== $currentReceipt) {
            Storage::disk('local')->delete($previousReceipt);
        }

        return new ProductOrderResource($line->productOrder->load('items'));
    }

    public function receipt(int $item, CurrentSalon $context)
    {
        $line = ProductOrderItem::query()->with('productOrder')->findOrFail($item);
        abort_unless($line->productOrder->salon_id === $context->get()->id, 404);
        abort_unless($line->receipt_path && Storage::disk('local')->exists($line->receipt_path), 404);

        return Storage::disk('local')->download($line->receipt_path, "receipt-order-{$line->product_order_id}-item-{$line->id}.".pathinfo($line->receipt_path, PATHINFO_EXTENSION));
    }
}
