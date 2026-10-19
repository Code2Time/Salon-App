<?php

namespace Tests\Feature\Products;

use App\Enums\SalonRole;
use App\Models\Product;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_only_exposes_active_products_of_selected_salon(): void
    {
        [$salon, , , $product] = $this->fixture();
        Product::create(['salon_id' => $salon->id, 'name' => 'غیرفعال', 'price' => 100, 'stock' => 1, 'is_active' => false]);
        $other = Salon::create(['name' => 'دیگر', 'slug' => 'other', 'is_active' => true]);
        Product::create(['salon_id' => $other->id, 'name' => 'خصوصی', 'price' => 100, 'stock' => 2]);
        $this->getJson('/api/catalog/products')->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $product->id)->assertJsonMissingPath('data.0.recommendations');
    }

    public function test_order_is_unpaid_reserves_stock_and_notifies_only_active_salon_owners(): void
    {
        [$salon, $owner, $customer, $product] = $this->fixture();
        $unrelated = User::factory()->create();
        $salon->users()->attach($unrelated, ['role' => SalonRole::Owner->value, 'is_active' => false]);
        $payload = $this->payload($salon, $product, 2);
        $response = $this->actingAs($customer)->postJson('/api/orders', $payload)->assertCreated()
            ->assertJsonPath('data.status', 'registered')->assertJsonPath('data.payment_status', 'unpaid')
            ->assertJsonPath('data.total_amount', 900000)->assertJsonPath('data.items.0.unit_price', 450000);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseCount('notifications', 1);
        $notice = $owner->notifications()->sole();
        $this->assertSame('/panel/orders/'.$response->json('data.id'), $notice->data['path']);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $unrelated->id]);
        $this->actingAs($owner)->getJson('/api/panel/orders')->assertOk()->assertJsonPath('data.0.customer_mobile', $customer->mobile);
    }

    public function test_repeated_submission_is_idempotent_and_different_payload_is_rejected(): void
    {
        [$salon, , $customer, $product] = $this->fixture();
        $payload = $this->payload($salon, $product, 2);
        $first = $this->actingAs($customer)->postJson('/api/orders', $payload)->assertCreated()->json('data.id');
        $this->actingAs($customer)->postJson('/api/orders', $payload)->assertCreated()->assertJsonPath('data.id', $first);
        $this->actingAs($customer)->postJson('/api/orders', [...$payload, 'notes' => 'متن متفاوت'])->assertStatus(409);
        $this->assertDatabaseCount('product_orders', 1);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_insufficient_stock_rolls_back_entire_order_and_creates_no_notifications(): void
    {
        [$salon, , $customer, $first] = $this->fixture();
        $second = Product::create(['salon_id' => $salon->id, 'name' => 'ماسک', 'price' => 100, 'stock' => 1]);
        $payload = $this->payload($salon, $first, 2);
        $payload['items'][] = ['product_id' => $second->id, 'quantity' => 2, 'expected_unit_price' => 100];
        $this->actingAs($customer)->postJson('/api/orders', $payload)->assertUnprocessable();
        $this->assertSame(5, $first->fresh()->stock);
        $this->assertSame(1, $second->fresh()->stock);
        $this->assertDatabaseCount('product_orders', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_orders_cannot_oversell_last_available_unit(): void
    {
        [$salon, , $customer, $product] = $this->fixture();
        $product->update(['stock' => 1]);
        $this->actingAs($customer)->postJson('/api/orders', $this->payload($salon, $product))->assertCreated();
        $this->actingAs(User::factory()->create())->postJson('/api/orders', $this->payload($salon, $product))->assertUnprocessable();
        $this->assertSame(0, $product->fresh()->stock);
        $this->assertDatabaseCount('product_orders', 1);
    }

    public function test_customer_ownership_and_salon_scoping_of_order_details(): void
    {
        [$salon, $owner, $customer, $product] = $this->fixture();
        $id = $this->actingAs($customer)->postJson('/api/orders', $this->payload($salon, $product))->assertCreated()->json('data.id');
        $this->actingAs($customer)->getJson("/api/orders/{$id}")->assertOk();
        $stranger = User::factory()->create();
        $this->actingAs($stranger)->getJson("/api/orders/{$id}")->assertNotFound();
        $this->actingAs($stranger)->getJson('/api/orders')->assertJsonPath('meta.total', 0);
        $other = Salon::create(['name' => 'دیگر', 'slug' => 'other', 'is_active' => true]);
        $other->users()->attach($stranger, ['role' => SalonRole::Owner->value, 'is_active' => true]);
        $this->actingAs($stranger)->getJson("/api/panel/orders/{$id}")->assertNotFound();
        $this->actingAs($owner)->getJson("/api/panel/orders/{$id}")->assertOk();
    }

    public function test_prices_and_names_remain_snapshotted_after_product_edit_and_delete(): void
    {
        [$salon, , $customer, $product] = $this->fixture();
        $id = $this->actingAs($customer)->postJson('/api/orders', $this->payload($salon, $product))->assertCreated()->json('data.id');
        $product->update(['name' => 'نام جدید', 'price' => 500000]);
        $product->delete();
        $this->actingAs($customer)->getJson("/api/orders/{$id}")->assertOk()->assertJsonPath('data.items.0.product_name', 'شامپو')->assertJsonPath('data.items.0.unit_price', 450000);
    }

    public function test_invalid_inactive_cross_salon_and_duplicate_products_are_rejected(): void
    {
        [$salon, , $customer, $product] = $this->fixture();
        $payload = $this->payload($salon, $product);
        $this->actingAs($customer)->postJson('/api/orders', [...$payload, 'items' => [$payload['items'][0], $payload['items'][0]]])->assertUnprocessable();
        $this->actingAs($customer)->postJson('/api/orders', [...$payload, 'items' => [['product_id' => $product->id, 'quantity' => -1, 'expected_unit_price' => 450000]]])->assertUnprocessable();
        $this->actingAs($customer)->postJson('/api/orders', [...$payload, 'items' => [['product_id' => $product->id, 'quantity' => 1, 'expected_unit_price' => 1]]])->assertUnprocessable();
        $product->update(['is_active' => false]);
        $this->actingAs($customer)->postJson('/api/orders', $payload)->assertUnprocessable();
        $other = Salon::create(['name' => 'دیگر', 'slug' => 'other', 'is_active' => true]);
        $product->update(['is_active' => true, 'salon_id' => $other->id]);
        $this->actingAs($customer)->postJson('/api/orders', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('product_orders', 0);
    }

    public function test_guest_cannot_order_or_read_orders(): void
    {
        $this->postJson('/api/orders', [])->assertUnauthorized();
        $this->getJson('/api/orders')->assertUnauthorized();
        $this->getJson('/api/panel/orders')->assertUnauthorized();
    }

    private function fixture(): array
    {
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'ara', 'is_active' => true]);
        $owner = User::factory()->create();
        $salon->users()->attach($owner, ['role' => SalonRole::Owner->value, 'is_active' => true]);
        $customer = User::factory()->create();
        $product = Product::create(['salon_id' => $salon->id, 'name' => 'شامپو', 'price' => 450000, 'stock' => 5]);

        return [$salon, $owner, $customer, $product];
    }

    private function payload(Salon $salon, Product $product, int $quantity = 1): array
    {
        return ['salon_id' => $salon->id, 'request_id' => (string) Str::uuid(), 'notes' => 'هماهنگی در سالن', 'items' => [['product_id' => $product->id, 'quantity' => $quantity, 'expected_unit_price' => $product->price]]];
    }
}
