<?php

namespace Tests\Feature;

use Tests\TestCase;

class LayoutRoutesTest extends TestCase
{
    public function test_layout_routes_support_direct_navigation(): void
    {
        $this->withoutVite();

        foreach (['/', '/panel', '/panel/customers', '/app', '/app/booking', '/panel/unknown', '/unknown-page'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('lang="fa" dir="rtl"', false)
                ->assertSee('id="app"', false);
        }
    }

    public function test_spa_fallback_does_not_capture_api_or_asset_requests(): void
    {
        foreach (['/api', '/api/services', '/build/missing', '/storage/missing', '/assets/missing', '/missing.js'] as $path) {
            $response = $this->get($path);

            $this->assertContains($response->status(), [403, 404], $path);
            $response->assertDontSee('id="app"', false);
        }
    }
}
