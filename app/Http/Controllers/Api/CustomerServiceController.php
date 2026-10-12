<?php

namespace App\Http\Controllers\Api;

use App\Enums\SalonRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerServiceResource;
use App\Models\Salon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerServiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'salon' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer'],
            'sort' => ['nullable', Rule::in(['newest', 'price_asc', 'price_desc'])],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:20'],
        ]);
        $salon = $this->salon($filters['salon'] ?? null);
        $query = $salon->services()
            ->where('is_active', true)
            ->whereHas('categories', fn ($category) => $category->where('is_active', true))
            ->with([
                'categories' => fn ($category) => $category
                    ->where('is_active', true)->orderBy('sort_order'),
                'providers' => fn ($provider) => $provider
                    ->where('salon_user.is_active', true)
                    ->where('salon_user.role', SalonRole::Employee->value)
                    ->whereHas('user', fn ($user) => $user->where('is_active', true))
                    ->with('user:id,name,image'),
            ]);

        if ($search = $filters['search'] ?? null) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($categoryId = $filters['category_id'] ?? null) {
            $query->whereHas('categories', fn ($category) => $category
                ->where('service_categories.salon_id', $salon->id)
                ->where('service_categories.is_active', true)
                ->whereKey($categoryId));
        }

        match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->latest(),
        };

        return CustomerServiceResource::collection(
            $query->paginate($filters['per_page'] ?? 5)->withQueryString(),
        )->additional(['salon' => ['id' => $salon->id, 'name' => $salon->name]]);
    }

    public function categories(Request $request): JsonResponse
    {
        $request->validate(['salon' => ['nullable', 'string', 'max:100']]);
        $salon = $this->salon($request->string('salon')->toString() ?: null);
        $categories = $salon->serviceCategories()
            ->where('is_active', true)
            ->whereHas('services', fn ($service) => $service->where('is_active', true))
            ->withCount(['services' => fn ($service) => $service->where('is_active', true)])
            ->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('name')
            ->get()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'image_url' => $category->image
                    ? (Str::startsWith($category->image, ['http://', 'https://', '/'])
                        ? $category->image : Storage::url($category->image))
                    : null,
                'is_featured' => $category->is_featured,
                'services_count' => $category->services_count,
            ]);

        return response()->json(['data' => $categories]);
    }

    private function salon(?string $slug): Salon
    {
        return Salon::query()
            ->where('is_active', true)
            ->when($slug, fn ($query) => $query->where('slug', $slug))
            ->when(! $slug, fn ($query) => $query->orderByRaw("CASE WHEN slug = 'ara' THEN 0 ELSE 1 END"))
            ->firstOrFail();
    }
}
