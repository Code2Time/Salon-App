<?php

namespace Tests\Feature\Panel;

use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SalonSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_salon_exposes_only_public_fields_and_respects_active_slug(): void
    {
        $salon = Salon::create(['name' => 'سالن آرا', 'slug' => 'ara', 'description' => 'معرفی سالن', 'phone' => '02112345678', 'address' => 'تهران']);
        $this->getJson('/api/catalog/salon')->assertOk()->assertJsonPath('data.name', $salon->name)
            ->assertJsonPath('data.phone', '02112345678')->assertJsonMissingPath('data.users')->assertJsonMissingPath('data.is_active');
        Salon::create(['name' => 'دوم', 'slug' => 'other', 'is_active' => true]);
        $this->getJson('/api/catalog/salon?salon=other')->assertOk()->assertJsonPath('data.name', 'دوم');
        $salon->update(['is_active' => false]);
        $this->getJson('/api/catalog/salon?salon=ara')->assertNotFound();
        $this->getJson('/api/catalog/salon?salon=missing')->assertNotFound();
    }

    public function test_owner_updates_own_salon_and_public_catalog_immediately_reflects_changes(): void
    {
        [$owner, $salon] = $this->fixture();
        $this->actingAs($owner)->postJson('/api/panel/salon', [
            'name' => '  سالن نیلوفر  ', 'description' => 'تجربه زیبایی', 'phone' => '۰۲۱۱۲۳۴۵۶۷۸',
            'address' => 'تهران، خیابان اول', 'instagram_url' => 'https://instagram.com/salon',
        ])->assertOk()->assertJsonPath('data.name', 'سالن نیلوفر')->assertJsonPath('data.phone', '02112345678');
        $this->getJson('/api/catalog/salon')->assertOk()->assertJsonPath('data.description', 'تجربه زیبایی');
        $this->assertDatabaseHas('salons', ['id' => $salon->id, 'name' => 'سالن نیلوفر', 'slug' => 'ara']);
        $this->postJson('/api/panel/salon', ['name' => 'سالن نیلوفر', 'description' => '', 'phone' => '', 'address' => '', 'instagram_url' => ''])
            ->assertOk()->assertJsonPath('data.description', null)->assertJsonPath('data.instagram_url', null);
    }

    public function test_settings_are_scoped_and_cannot_change_salon_identity(): void
    {
        [$owner, $salon] = $this->fixture();
        $other = Salon::create(['name' => 'خصوصی', 'slug' => 'other']);
        $this->actingAs($owner)->withHeader('X-Salon-ID', $other->id)->postJson('/api/panel/salon', ['name' => 'تغییر'])->assertForbidden();
        $this->getJson('/api/panel/salon')->assertForbidden();
        $this->flushHeaders();
        $this->postJson('/api/panel/salon', ['name' => 'تغییر', 'salon_id' => $other->id, 'slug' => 'other'])
            ->assertUnprocessable()->assertJsonValidationErrors(['salon_id', 'slug']);
        $this->assertDatabaseHas('salons', ['id' => $salon->id, 'name' => 'آرا']);
        $this->assertDatabaseHas('salons', ['id' => $other->id, 'name' => 'خصوصی']);
    }

    public function test_employee_can_read_current_salon_but_only_owner_and_super_admin_can_update(): void
    {
        [, $salon] = $this->fixture();
        $employee = User::factory()->create();
        $salon->users()->attach($employee, ['role' => 'employee', 'is_active' => true]);
        $this->actingAs($employee)->getJson('/api/panel/salon')->assertOk();
        $this->postJson('/api/panel/salon', ['name' => 'غیرمجاز'])->assertForbidden();
        $customer = User::factory()->create();
        $this->actingAs($customer)->getJson('/api/panel/salon')->assertForbidden();
        $this->postJson('/api/panel/salon', ['name' => 'غیرمجاز'])->assertForbidden();
        $admin = User::factory()->create(['system_role' => 'super_admin']);
        $this->actingAs($admin)->postJson('/api/panel/salon', ['name' => 'جدید'])->assertOk();
    }

    public function test_logo_replacement_and_removal_clean_old_files_and_validation_preserves_them(): void
    {
        Storage::fake('public');
        [$owner, $salon] = $this->fixture();
        $this->actingAs($owner)->post('/api/panel/salon', ['name' => 'آرا', 'logo' => UploadedFile::fake()->image('logo.png')], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.logo_url', fn ($url) => str_starts_with($url, '/storage/salons/'.$salon->id.'/'));
        $first = $salon->fresh()->logo;
        Storage::disk('public')->assertExists($first);
        $this->post('/api/panel/salon', ['name' => '', 'logo' => UploadedFile::fake()->image('bad.png')], ['Accept' => 'application/json'])->assertUnprocessable();
        Storage::disk('public')->assertExists($first);
        $this->post('/api/panel/salon', ['name' => 'آرا', 'logo' => UploadedFile::fake()->image('new.jpg')], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('public')->assertMissing($first);
        $second = $salon->fresh()->logo;
        Storage::disk('public')->assertExists($second);
        $this->postJson('/api/panel/salon', ['name' => 'آرا', 'remove_logo' => true])->assertOk()->assertJsonPath('data.logo_url', null);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_invalid_links_phone_and_logo_are_rejected_and_guest_cannot_write(): void
    {
        [$owner] = $this->fixture();
        $this->postJson('/api/panel/salon', ['name' => 'جدید'])->assertUnauthorized();
        $this->actingAs($owner)->postJson('/api/panel/salon', ['name' => ' ', 'phone' => 'invalid', 'website_url' => 'javascript:alert(1)', 'telegram_url' => 'ftp://example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'phone', 'website_url', 'telegram_url']);
        $this->post('/api/panel/salon', ['name' => 'آرا', 'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('logo');
    }

    private function fixture(): array
    {
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'ara']);
        $owner = User::factory()->create();
        $salon->users()->attach($owner, ['role' => 'salon_owner', 'is_active' => true]);

        return [$owner, $salon];
    }
}
