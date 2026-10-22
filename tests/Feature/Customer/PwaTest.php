<?php

namespace Tests\Feature\Customer;

use App\Models\Salon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_is_public_and_uses_current_salon_identity_with_stable_customer_start(): void
    {
        Salon::create(['name' => 'سالن نیلوفر', 'slug' => 'ara', 'phone' => '02112345678']);
        $response = $this->get('/app.webmanifest')->assertOk()->assertHeader('Content-Type', 'application/manifest+json');
        $response->assertJsonPath('name', 'سالن نیلوفر')->assertJsonPath('id', '/app')->assertJsonPath('start_url', '/app?source=pwa')
            ->assertJsonPath('display', 'standalone')->assertJsonPath('lang', 'fa')->assertJsonPath('dir', 'rtl')
            ->assertJsonPath('icons.0.sizes', '192x192')->assertJsonPath('icons.1.sizes', '512x512')
            ->assertJsonMissingPath('phone')->assertJsonMissingPath('users');
        $this->assertStringStartsWith('/app-icon/192.png?', $response->json('icons.0.src'));
    }

    public function test_inactive_salon_is_not_used_and_empty_database_has_a_generic_manifest(): void
    {
        $this->get('/app.webmanifest')->assertOk()->assertJsonPath('name', 'سالن زیبایی');
        Salon::create(['name' => 'غیرفعال', 'slug' => 'ara', 'is_active' => false]);
        Salon::create(['name' => 'فعال', 'slug' => 'active', 'is_active' => true]);
        $this->get('/app.webmanifest')->assertOk()->assertJsonPath('name', 'فعال');
    }

    public function test_icon_sizes_are_real_pngs_and_uploaded_logo_is_used_without_exposing_storage_path(): void
    {
        Storage::fake('public');
        $logo = UploadedFile::fake()->image('logo.png', 100, 100)->store('salons/1', 'public');
        Salon::create(['name' => 'آرا', 'slug' => 'ara', 'logo' => $logo]);
        foreach ([180, 192, 512] as $size) {
            $response = $this->get('/app-icon/'.$size.'.png')->assertOk()->assertHeader('Content-Type', 'image/png');
            $dimensions = getimagesizefromstring($response->getContent());
            $this->assertSame($size, $dimensions[0]);
            $this->assertSame($size, $dimensions[1]);
        }
        $this->get('/app-icon/100.png')->assertNotFound();
        $this->get('/app.webmanifest')->assertJsonMissingPath('logo');
    }

    public function test_missing_logo_falls_back_to_generated_icons_and_logo_changes_version_the_icon_url(): void
    {
        $salon = Salon::create(['name' => 'آرا', 'slug' => 'ara']);
        $firstIcon = $this->get('/app.webmanifest')->json('icons.0.src');
        $response = $this->get('/app-icon/192.png')->assertOk();
        $this->assertSame(192, getimagesize($response->baseResponse->getFile()->getPathname())[0]);
        $salon->update(['logo' => 'salons/1/missing.png']);
        $this->get('/app-icon/512.png')->assertOk();
        $this->assertNotSame($firstIcon, $this->get('/app.webmanifest')->json('icons.0.src'));
    }

    public function test_customer_install_page_contains_manifest_and_apple_metadata(): void
    {
        $this->get('/app/install')->assertOk()->assertSee('rel="manifest"', false)
            ->assertSee('/app.webmanifest')->assertSee('/app-icon/180.png')->assertSee('apple-mobile-web-app-capable');
    }
}
