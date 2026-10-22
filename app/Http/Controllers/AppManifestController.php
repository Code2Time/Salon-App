<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use Illuminate\Support\Facades\Storage;

class AppManifestController extends Controller
{
    public function manifest()
    {
        $salon = $this->salon();
        $version = substr(sha1($salon?->logo ?? 'default'), 0, 12);

        return response()->json([
            'id' => '/app', 'name' => $salon?->name ?? 'سالن زیبایی',
            'short_name' => mb_substr($salon?->name ?? 'سالن زیبایی', 0, 24),
            'description' => 'خدمات، رزرو نوبت و محصولات مراقبتی سالن',
            'lang' => 'fa', 'dir' => 'rtl', 'start_url' => '/app?source=pwa', 'scope' => '/',
            'display' => 'standalone', 'background_color' => '#fff7fa', 'theme_color' => '#b21e5b',
            'icons' => array_map(fn ($size) => [
                'src' => "/app-icon/{$size}.png?v={$version}", 'sizes' => "{$size}x{$size}",
                'type' => 'image/png', 'purpose' => 'any maskable',
            ], [192, 512]),
            'shortcuts' => [
                ['name' => 'رزرو نوبت', 'url' => '/app/services'],
                ['name' => 'نوبت‌های من', 'url' => '/app/appointments'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'no-cache']);
    }

    public function icon(int $size)
    {
        abort_unless(in_array($size, [180, 192, 512], true), 404);
        $salon = $this->salon();
        $disk = Storage::disk('public');
        if ($salon?->logo && function_exists('imagecreatefromstring') && $disk->exists($salon->logo)) {
            $bytes = $disk->get($salon->logo);
            $dimensions = @getimagesizefromstring($bytes);
            // Keep decoding bounded even if an old uploaded file is unusually large.
            if ($dimensions && $dimensions[0] <= 4096 && $dimensions[1] <= 4096) {
                $source = @imagecreatefromstring($bytes);
                if ($source) {
                    $canvas = imagecreatetruecolor($size, $size);
                    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 178, 30, 91));
                    // A 56% square fits entirely inside the maskable icon's safe circle.
                    $scale = $size * .56 / max($dimensions[0], $dimensions[1]);
                    $width = (int) round($dimensions[0] * $scale);
                    $height = (int) round($dimensions[1] * $scale);
                    imagecopyresampled($canvas, $source, (int) (($size - $width) / 2), (int) (($size - $height) / 2), 0, 0, $width, $height, $dimensions[0], $dimensions[1]);
                    ob_start();
                    imagepng($canvas);
                    $png = ob_get_clean();

                    return response($png, 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=300']);
                }
            }
        }

        return response()->file(public_path("pwa/icon-{$size}.png"), ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=300']);
    }

    private function salon(): ?Salon
    {
        return Salon::query()->where('is_active', true)->orderByRaw("CASE WHEN slug = 'ara' THEN 0 ELSE 1 END")->orderBy('id')->first();
    }
}
