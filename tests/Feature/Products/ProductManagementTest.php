<?php

namespace Tests\Feature\Products;

use App\Enums\SalonRole;
use App\Models\Product;
use App\Models\Salon;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_store_edit_filter_and_soft_delete_product_with_service_links(): void
    {
        Storage::fake('public');
        [$salon, $owner] = $this->fixture();
        $service = Service::create(['salon_id' => $salon->id, 'name' => 'کراتین', 'price' => 100000, 'duration_minutes' => 60, 'is_active' => true]);
        $input = [
            'name' => 'شامپو', 'description' => 'مراقبت مو', 'price' => 450000, 'stock' => 5, 'is_active' => true,
            'recommendations' => [['service_id' => $service->id, 'recommendation_text' => 'برای ماندگاری کراتین', 'recommendation_enabled' => true, 'discount_enabled' => true, 'discount_percent' => 10]],
        ];
        $image = UploadedFile::fake()->createWithContent('test.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $created = $this->actingAs($owner)->post('/api/panel/products', [...$input, 'image' => $image], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.recommendations.0.service_id', $service->id);
        $product = Product::findOrFail($created->json('data.id'));
        Storage::disk('public')->assertExists($product->image);
        $this->actingAs($owner)->getJson('/api/panel/products?search=شامپو&stock=available&status=active')->assertOk()->assertJsonPath('meta.total', 1);
        $this->actingAs($owner)->getJson('/api/panel/products/options')->assertOk()->assertJsonPath('data.0.id', $service->id);
        $this->actingAs($owner)->postJson("/api/panel/products/{$product->id}", [...$input, 'stock' => 0, 'remove_image' => true, 'recommendations' => []])
            ->assertOk()->assertJsonCount(0, 'data.recommendations')->assertJsonPath('data.image_url', null);
        Storage::disk('public')->assertMissing($product->image);
        $this->actingAs($owner)->deleteJson("/api/panel/products/{$product->id}")->assertOk();
        $this->assertSoftDeleted($product);
    }

    public function test_tenant_and_employee_permissions_are_enforced(): void
    {
        [$salon, $owner] = $this->fixture();
        [$other] = $this->fixture('other');
        $product = Product::create(['salon_id' => $other->id, 'name' => 'خصوصی', 'price' => 10, 'stock' => 1]);
        $this->actingAs($owner)->getJson('/api/panel/products')->assertJsonPath('meta.total', 0);
        $this->actingAs($owner)->deleteJson("/api/panel/products/{$product->id}")->assertNotFound();
        $this->actingAs($owner)->postJson("/api/panel/products/{$product->id}", [])->assertNotFound();
        $employee = User::factory()->create();
        $salon->users()->attach($employee, ['role' => SalonRole::Employee->value, 'is_active' => true, 'permissions' => ['products']]);
        $this->actingAs($employee)->getJson('/api/panel/products')->assertOk();
        $this->actingAs($employee)->postJson('/api/panel/products', [])->assertForbidden();
        $this->actingAs($employee)->getJson('/api/panel/orders')->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson('/api/panel/products')->assertForbidden();
    }

    public function test_cross_salon_links_invalid_stock_and_invalid_discounts_are_rejected(): void
    {
        [$salon, $owner] = $this->fixture();
        [$other] = $this->fixture('other');
        $service = Service::create(['salon_id' => $other->id, 'name' => 'دیگر', 'price' => 100, 'duration_minutes' => 60]);
        $base = ['name' => 'محصول', 'price' => 100, 'stock' => 1, 'is_active' => true, 'recommendations' => []];
        $link = ['service_id' => $service->id, 'recommendation_text' => 'متن', 'recommendation_enabled' => true, 'discount_enabled' => true, 'discount_percent' => 10];
        $this->actingAs($owner)->postJson('/api/panel/products', [...$base, 'recommendations' => [$link]])->assertUnprocessable()->assertJsonValidationErrors('recommendations.0.service_id');
        $this->actingAs($owner)->postJson('/api/panel/products', [...$base, 'stock' => -1])->assertUnprocessable()->assertJsonValidationErrors('stock');
        $service->update(['salon_id' => $salon->id]);
        $this->actingAs($owner)->postJson('/api/panel/products', [...$base, 'recommendations' => [[...$link, 'discount_percent' => 0]]])->assertUnprocessable()->assertJsonValidationErrors('recommendations.0.discount_percent');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_multipart_recommendations_json_preserves_empty_and_nested_values(): void
    {
        [, $owner] = $this->fixture();
        $this->actingAs($owner)->post('/api/panel/products', ['name' => 'سرم', 'price' => '100', 'stock' => '2', 'is_active' => '1', 'recommendations_json' => '[]'], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonCount(0, 'data.recommendations');
    }

    private function fixture(string $slug = 'ara'): array
    {
        $salon = Salon::create(['name' => $slug, 'slug' => $slug, 'is_active' => true]);
        $owner = User::factory()->create();
        $salon->users()->attach($owner, ['role' => SalonRole::Owner->value, 'is_active' => true]);

        return [$salon, $owner];
    }
}
