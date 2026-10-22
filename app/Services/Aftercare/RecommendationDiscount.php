<?php

namespace App\Services\Aftercare;

use App\Models\AftercareRecommendation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RecommendationDiscount
{
    public function find(User $customer, int $salonId, ?string $code, array $productIds, bool $lock): ?AftercareRecommendation
    {
        if (! $code) {
            return null;
        }
        $query = AftercareRecommendation::query()->where('customer_id', $customer->id)
            ->where('salon_id', $salonId)->where('discount_code', $code);
        $recommendation = ($lock ? $query->lockForUpdate() : $query)->first();
        if (! $recommendation || ! $recommendation->discountIsAvailable()
            || ! in_array($recommendation->product_id, $productIds, true)) {
            throw ValidationException::withMessages(['discount_code' => 'کد تخفیف معتبر نیست، منقضی یا قبلاً استفاده شده است؛ محصول مرتبط را در سبد قرار دهید.']);
        }

        return $recommendation;
    }
}
