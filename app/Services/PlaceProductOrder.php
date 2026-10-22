<?php

namespace App\Services;

use App\Enums\SalonRole;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\Salon;
use App\Models\User;
use App\Notifications\ProductOrderPlaced;
use App\Services\Aftercare\RecommendationDiscount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceProductOrder
{
    public function quote(User $customer, array $data): array
    {
        $pricing = $this->pricing($customer, $data, false);

        return [
            'subtotal_amount' => $pricing['subtotal'], 'discount_amount' => $pricing['discount'],
            'total_amount' => $pricing['total'], 'discount_code' => $pricing['recommendation']?->discount_code,
            'discount_percent' => $pricing['recommendation']?->discount_percent ?? 0,
            'product_id' => $pricing['recommendation']?->product_id,
        ];
    }

    public function handle(User $customer, array $data): ProductOrder
    {
        $items = collect($data['items'])->sortBy('product_id')->values();
        $hashParts = [$data['salon_id'], $items->all(), $data['notes'] ?? null];
        if (filled($data['discount_code'] ?? null)) {
            $hashParts[] = $data['discount_code'];
        }
        // Preserve the request hashes of existing orders without a discount.
        $hash = hash('sha256', json_encode($hashParts));

        return DB::transaction(function () use ($customer, $data, $hash) {
            User::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $existing = ProductOrder::query()->where('customer_id', $customer->id)->where('request_id', $data['request_id'])->first();
            if ($existing) {
                abort_unless(hash_equals($existing->request_hash, $hash), 409, 'این شناسه قبلاً برای سفارش دیگری استفاده شده است.');

                return $existing->load('items');
            }
            $pricing = $this->pricing($customer, $data, true);
            $recommendation = $pricing['recommendation'];
            foreach ($pricing['lines'] as $line) {
                $pricing['products']->get($line['product_id'])->decrement('stock', $line['quantity']);
            }
            $order = ProductOrder::create([
                'salon_id' => $pricing['salon']->id, 'customer_id' => $customer->id,
                'request_id' => $data['request_id'], 'request_hash' => $hash,
                'status' => 'registered', 'payment_status' => 'unpaid', 'total_amount' => $pricing['total'],
                'subtotal_amount' => $pricing['subtotal'], 'discount_amount' => $pricing['discount'],
                'discount_code' => $recommendation?->discount_code, 'aftercare_recommendation_id' => $recommendation?->id,
                'customer_name' => $customer->name, 'customer_mobile' => $customer->mobile,
                'notes' => $data['notes'] ?? null,
            ]);
            $order->items()->createMany($pricing['lines']);
            $recommendation?->update(['redeemed_at' => now()]);
            $owners = $pricing['salon']->users()->wherePivot('role', SalonRole::Owner->value)
                ->wherePivot('is_active', true)->where('users.is_active', true)->get();
            foreach ($owners as $owner) {
                $owner->notify(new ProductOrderPlaced($order->id, $customer->name, $pricing['salon']->id));
            }

            return $order->load('items');
        }, 3);
    }

    private function pricing(User $customer, array $data, bool $lock): array
    {
        $items = collect($data['items'])->sortBy('product_id')->values();
        $salon = Salon::query()->where('is_active', true)->findOrFail($data['salon_id']);
        $query = Product::query()->where('salon_id', $salon->id)->whereIn('id', $items->pluck('product_id'))->orderBy('id');
        $products = ($lock ? $query->lockForUpdate() : $query)->get()->keyBy('id');
        $recommendation = app(RecommendationDiscount::class)->find($customer, $salon->id, $data['discount_code'] ?? null, $items->pluck('product_id')->all(), $lock);
        $subtotal = 0;
        $discount = 0;
        $lines = [];
        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            if (! $product || ! $product->is_active || $product->stock < $item['quantity']) {
                throw ValidationException::withMessages(['items' => 'یکی از محصولات موجود نیست یا موجودی کافی ندارد؛ سبد را بررسی کنید.']);
            }
            if ($product->price !== (int) $item['expected_unit_price']) {
                throw ValidationException::withMessages(['items' => 'قیمت یکی از محصولات تغییر کرده است؛ محصول را از سبد حذف و با قیمت جدید اضافه کنید.']);
            }
            $amount = $product->price * $item['quantity'];
            $percent = $recommendation?->product_id === $product->id ? $recommendation->discount_percent : 0;
            $lineDiscount = intdiv($product->price * $percent, 100) * $item['quantity'];
            $subtotal += $amount;
            $discount += $lineDiscount;
            $lines[] = ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => $product->price,
                'quantity' => $item['quantity'], 'total_amount' => $amount - $lineDiscount,
                'discount_percent' => $percent, 'discount_amount' => $lineDiscount];
        }

        return ['salon' => $salon, 'products' => $products, 'recommendation' => $recommendation, 'lines' => $lines,
            'subtotal' => $subtotal, 'discount' => $discount, 'total' => $subtotal - $discount];
    }
}
