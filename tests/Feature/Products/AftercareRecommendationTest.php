<?php

namespace Tests\Feature\Products;

use App\Models\AftercareRecommendation;
use App\Models\Appointment;
use App\Models\Product;
use App\Models\Salon;
use App\Models\Service;
use App\Models\User;
use App\Notifications\AftercareProductsSuggested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AftercareRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_completion_issues_one_personal_offer_per_product_with_highest_discount_and_one_notice(): void
    {
        [$salon, $owner, $customer, $appointment, $product, $service] = $this->fixture();
        $second = $salon->services()->create(['name' => 'رنگ مو', 'price' => 200, 'duration_minutes' => 30]);
        $appointment->items()->create(['salon_user_id' => $appointment->items->first()->salon_user_id, 'service_id' => $second->id, 'position' => 1, 'service_name' => 'رنگ مو', 'price' => 200, 'duration_minutes' => 30, 'starts_at' => now(), 'ends_at' => now()->addMinutes(30)]);
        $product->services()->attach($second, ['recommendation_enabled' => true, 'recommendation_text' => 'مراقبت رنگ', 'discount_enabled' => true, 'discount_percent' => 25]);
        $this->complete($owner, $appointment)->assertOk();
        $this->complete($owner, $appointment)->assertOk();
        $offer = AftercareRecommendation::sole();
        $this->assertSame(25, $offer->discount_percent);
        $this->assertCount(2, $offer->care_instructions);
        $this->assertSame($customer->id, $offer->customer_id);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', ['type' => AftercareProductsSuggested::class, 'notifiable_id' => $customer->id]);
        $this->assertEqualsWithDelta(now()->addDays(3)->timestamp, $offer->expires_at->timestamp, 2);
        $product->services()->updateExistingPivot($service->id, ['discount_percent' => 99, 'recommendation_text' => 'جدید']);
        $salon->update(['aftercare_discount_validity_days' => 30]);
        $this->assertSame(25, $offer->fresh()->discount_percent);
        $this->actingAs($customer)->getJson('/api/recommendations')->assertOk()->assertJsonPath('data.0.discounted_price', 750)
            ->assertJsonPath('data.0.care_instructions.1.text', 'مراقبت رنگ');
    }

    public function test_only_completed_emits_offers_and_owner_can_record_real_completion_after_expiry(): void
    {
        [, $owner, , $appointment] = $this->fixture();
        foreach (['cancelled', 'no_show', 'rejected', 'pending'] as $status) {
            $appointment->update(['status' => $status]);
            $this->complete($owner, $appointment)->assertUnprocessable();
        }
        $this->assertDatabaseCount('aftercare_recommendations', 0);
        $this->assertDatabaseCount('notifications', 0);
        $appointment->update(['status' => 'confirmed', 'starts_at' => now()->subHours(2), 'ends_at' => now()->subHour()]);
        $employee = $appointment->items->first()->provider->user;
        $this->complete($employee, $appointment)->assertUnprocessable();
        $this->complete($owner, $appointment)->assertOk()->assertJsonPath('data.status', 'completed');
        $this->assertDatabaseCount('aftercare_recommendations', 1);
    }

    public function test_disabled_missing_inactive_and_unrelated_products_do_not_issue_offers(): void
    {
        [$salon, $owner, , $appointment, $product, $service] = $this->fixture();
        $product->services()->updateExistingPivot($service->id, ['recommendation_enabled' => false]);
        foreach ([['is_active' => false, 'stock' => 3], ['is_active' => true, 'stock' => 0]] as $values) {
            $other = $salon->services()->first();
            $p = Product::create(['salon_id' => $salon->id, 'name' => 'نامعتبر', 'price' => 100, ...$values]);
            $p->services()->attach($other, ['recommendation_enabled' => true, 'recommendation_text' => 'متن']);
        }
        Product::create(['salon_id' => $salon->id, 'name' => 'نامرتبط', 'price' => 100, 'stock' => 2]);
        $this->complete($owner, $appointment)->assertOk();
        $this->assertDatabaseCount('aftercare_recommendations', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_quote_does_not_consume_code_and_successful_order_consumes_once_with_idempotent_retries(): void
    {
        [$salon, $owner, $customer, $appointment, $product] = $this->fixture();
        $this->complete($owner, $appointment)->assertOk();
        $offer = AftercareRecommendation::sole();
        $payload = $this->payload($salon, $product, $offer->discount_code);
        $payload['items'][0]['quantity'] = 2;
        $this->actingAs($customer)->postJson('/api/orders/quote', $payload)->assertOk()->assertJsonPath('data.total_amount', 1800)->assertJsonPath('data.discount_amount', 200);
        $this->assertNull($offer->fresh()->redeemed_at);
        $order = $this->postJson('/api/orders', $payload)->assertCreated()->assertJsonPath('data.total_amount', 1800)
            ->assertJsonPath('data.subtotal_amount', 2000)->assertJsonPath('data.items.0.discount_amount', 200)->json('data.id');
        $this->assertNotNull($offer->fresh()->redeemed_at);
        $this->postJson('/api/orders', $payload)->assertCreated()->assertJsonPath('data.id', $order);
        $this->postJson('/api/orders', [...$payload, 'request_id' => (string) Str::uuid()])->assertUnprocessable()->assertJsonValidationErrors('discount_code');
        $this->assertDatabaseCount('product_orders', 1);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_foreign_customer_salon_wrong_product_and_stacking_codes_are_rejected(): void
    {
        [$salon, $owner, $customer, $appointment, $product] = $this->fixture();
        $this->complete($owner, $appointment)->assertOk();
        $code = AftercareRecommendation::sole()->discount_code;
        $payload = $this->payload($salon, $product, $code);
        $this->actingAs(User::factory()->create())->getJson('/api/recommendations')->assertOk()->assertJsonPath('meta.total', 0);
        $this->postJson('/api/orders', $payload)->assertUnprocessable();
        $otherSalon = Salon::create(['name' => 'دیگر', 'slug' => 'other']);
        $otherProduct = Product::create(['salon_id' => $otherSalon->id, 'name' => 'محصول دیگر', 'price' => 1000, 'stock' => 5]);
        $this->actingAs($customer)->postJson('/api/orders', $this->payload($otherSalon, $otherProduct, $code))->assertUnprocessable();
        $wrong = Product::create(['salon_id' => $salon->id, 'name' => 'غیرمرتبط', 'price' => 1000, 'stock' => 5]);
        $this->postJson('/api/orders', $this->payload($salon, $wrong, $code))->assertUnprocessable();
        $this->postJson('/api/orders', [...$payload, 'discount_codes' => [$code, $code]])->assertUnprocessable();
        $this->postJson('/api/orders', [...$payload, 'discount_code' => [$code, $code]])->assertUnprocessable();
        $this->assertDatabaseCount('product_orders', 0);
        $this->assertNull(AftercareRecommendation::sole()->redeemed_at);
    }

    public function test_expiration_and_current_price_are_enforced_and_order_failure_does_not_consume_offer(): void
    {
        [$salon, $owner, $customer, $appointment, $product] = $this->fixture();
        $this->complete($owner, $appointment)->assertOk();
        $offer = AftercareRecommendation::sole();
        $payload = $this->payload($salon, $product, $offer->discount_code);
        $product->update(['price' => 2000]);
        $this->actingAs($customer)->postJson('/api/orders', $payload)->assertUnprocessable();
        $this->assertNull($offer->fresh()->redeemed_at);
        $payload['items'][0]['expected_unit_price'] = 2000;
        $this->postJson('/api/orders/quote', $payload)->assertOk()->assertJsonPath('data.total_amount', 1800);
        $product->update(['stock' => 0]);
        $this->postJson('/api/orders', $payload)->assertUnprocessable();
        $this->assertNull($offer->fresh()->redeemed_at);
        $this->assertDatabaseCount('product_orders', 0);
        $product->update(['stock' => 5]);
        $offer->update(['expires_at' => now()->subSecond()]);
        $this->postJson('/api/orders', $payload)->assertUnprocessable()->assertJsonValidationErrors('discount_code');
        $this->getJson('/api/recommendations')->assertOk()->assertJsonPath('data.0.discount_available', false)->assertJsonPath('data.0.discounted_price', 2000);
    }

    public function test_recommendations_without_discount_remain_personal_and_unavailable_products_are_hidden(): void
    {
        [, $owner, $customer, $appointment, $product, $service] = $this->fixture();
        $product->services()->updateExistingPivot($service->id, ['discount_enabled' => false]);
        $this->complete($owner, $appointment)->assertOk();
        $this->actingAs($customer)->getJson('/api/recommendations')->assertOk()->assertJsonPath('data.0.discount_code', null)->assertJsonPath('data.0.discounted_price', 1000);
        $product->update(['is_active' => false]);
        $this->getJson('/api/recommendations')->assertOk()->assertJsonPath('meta.total', 0);
        $product->update(['is_active' => true]);
        $product->delete();
        $this->getJson('/api/recommendations')->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_only_matching_product_is_discounted_and_unit_rounding_matches_displayed_price(): void
    {
        [$salon, $owner, $customer, $appointment, $product] = $this->fixture();
        $product->update(['price' => 1003]);
        $other = Product::create(['salon_id' => $salon->id, 'name' => 'محصول عادی', 'price' => 500, 'stock' => 5]);
        $this->complete($owner, $appointment)->assertOk();
        $offer = AftercareRecommendation::sole();
        $payload = $this->payload($salon, $product, strtolower($offer->discount_code));
        $payload['items'][0]['quantity'] = 2;
        $payload['items'][] = ['product_id' => $other->id, 'quantity' => 1, 'expected_unit_price' => 500];
        $this->actingAs($customer)->getJson('/api/recommendations')->assertOk()->assertJsonPath('data.0.discounted_price', 903);
        $this->postJson('/api/orders', $payload)->assertCreated()->assertJsonPath('data.subtotal_amount', 2506)
            ->assertJsonPath('data.discount_amount', 200)->assertJsonPath('data.total_amount', 2306)
            ->assertJsonPath('data.items.0.total_amount', 1806)->assertJsonPath('data.items.1.discount_amount', 0);
    }

    public function test_manager_sets_validity_but_cannot_change_existing_expiry_and_guests_cannot_read_offers(): void
    {
        [$salon, $owner, , $appointment] = $this->fixture();
        $this->getJson('/api/recommendations')->assertUnauthorized();
        $this->postJson('/api/orders/quote', [])->assertUnauthorized();
        $this->actingAs($owner)->postJson('/api/panel/salon', ['name' => $salon->name, 'aftercare_discount_validity_days' => 14])
            ->assertOk()->assertJsonPath('data.aftercare_discount_validity_days', 14);
        $this->postJson('/api/panel/salon', ['name' => $salon->name, 'aftercare_discount_validity_days' => 0])->assertUnprocessable();
        $this->getJson('/api/catalog/salon')->assertOk()->assertJsonMissingPath('data.aftercare_discount_validity_days');
        $this->complete($owner, $appointment)->assertOk();
        $offer = AftercareRecommendation::sole();
        $expiry = $offer->expires_at->toISOString();
        $this->assertEqualsWithDelta(now()->addDays(14)->timestamp, $offer->expires_at->timestamp, 2);
        $this->postJson('/api/panel/salon', ['name' => $salon->name, 'aftercare_discount_validity_days' => 30])->assertOk();
        $this->assertSame($expiry, $offer->fresh()->expires_at->toISOString());
    }

    private function complete(User $owner, Appointment $appointment)
    {
        return $this->actingAs($owner)->putJson('/api/panel/appointments/'.$appointment->id.'/status', ['status' => 'completed']);
    }

    private function payload(Salon $salon, Product $product, string $code): array
    {
        return ['request_id' => (string) Str::uuid(), 'salon_id' => $salon->id, 'discount_code' => $code,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'expected_unit_price' => $product->price]]];
    }

    private function fixture(): array
    {
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'ara', 'aftercare_discount_validity_days' => 3]);
        $owner = User::factory()->create();
        $customer = User::factory()->create();
        $employee = User::factory()->create();
        $salon->users()->attach($owner, ['role' => 'salon_owner', 'is_active' => true]);
        $salon->users()->attach($employee, ['role' => 'employee', 'is_active' => true, 'permissions' => ['appointments']]);
        $member = $salon->users()->whereKey($employee->id)->first()->pivot;
        $service = Service::create(['salon_id' => $salon->id, 'name' => 'کوتاهی', 'price' => 100, 'duration_minutes' => 30]);
        $appointment = $salon->appointments()->create(['customer_id' => $customer->id, 'created_by_id' => $owner->id, 'source' => 'panel', 'status' => 'confirmed', 'starts_at' => now(), 'ends_at' => now()->addHour(), 'total_price' => 100, 'required_deposit_amount' => 0]);
        $appointment->items()->create(['service_id' => $service->id, 'salon_user_id' => $member->id, 'position' => 0, 'service_name' => 'کوتاهی', 'price' => 100, 'duration_minutes' => 30, 'starts_at' => now(), 'ends_at' => now()->addMinutes(30)]);
        $product = Product::create(['salon_id' => $salon->id, 'name' => 'شامپو', 'price' => 1000, 'stock' => 5, 'is_active' => true]);
        $product->services()->attach($service, ['recommendation_enabled' => true, 'recommendation_text' => 'روزانه استفاده کنید.', 'discount_enabled' => true, 'discount_percent' => 10]);

        return [$salon, $owner, $customer, $appointment, $product, $service];
    }
}
