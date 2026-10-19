<?php

namespace App\Services;

use App\Enums\SalonRole;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\Salon;
use App\Models\User;
use App\Notifications\ProductOrderPlaced;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceProductOrder
{
    public function handle(User $customer, array $data): ProductOrder
    {
        $items = collect($data['items'])->sortBy('product_id')->values();
        $hash = hash('sha256', json_encode([$data['salon_id'], $items->all(), $data['notes'] ?? null]));

        return DB::transaction(function () use ($customer, $data, $items, $hash) {
            User::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $existing = ProductOrder::query()->where('customer_id', $customer->id)->where('request_id', $data['request_id'])->first();
            if ($existing) {
                abort_unless(hash_equals($existing->request_hash, $hash), 409, 'این شناسه قبلاً برای سفارش دیگری استفاده شده است.');

                return $existing->load('items');
            }
            $salon = Salon::query()->where('is_active', true)->findOrFail($data['salon_id']);
            $products = Product::query()->where('salon_id', $salon->id)->whereIn('id', $items->pluck('product_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $total = 0;
            $snapshots = [];
            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                if (! $product || ! $product->is_active || $product->stock < $item['quantity']) {
                    throw ValidationException::withMessages(['items' => 'یکی از محصولات موجود نیست یا موجودی کافی ندارد؛ سبد را بررسی کنید.']);
                }
                if ($product->price !== (int) $item['expected_unit_price']) {
                    throw ValidationException::withMessages(['items' => 'قیمت یکی از محصولات تغییر کرده است؛ محصول را از سبد حذف و با قیمت جدید اضافه کنید.']);
                }
                $amount = $product->price * $item['quantity'];
                $total += $amount;
                $snapshots[] = ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => $product->price, 'quantity' => $item['quantity'], 'total_amount' => $amount];
                $product->decrement('stock', $item['quantity']);
            }
            $order = ProductOrder::create([
                'salon_id' => $salon->id, 'customer_id' => $customer->id,
                'request_id' => $data['request_id'], 'request_hash' => $hash,
                'status' => 'registered', 'payment_status' => 'unpaid', 'total_amount' => $total,
                'customer_name' => $customer->name, 'customer_mobile' => $customer->mobile,
                'notes' => $data['notes'] ?? null,
            ]);
            $order->items()->createMany($snapshots);
            $owners = $salon->users()->wherePivot('role', SalonRole::Owner->value)
                ->wherePivot('is_active', true)->where('users.is_active', true)->get();
            foreach ($owners as $owner) {
                $owner->notify(new ProductOrderPlaced($order->id, $customer->name));
            }

            return $order->load('items');
        }, 3);
    }
}
