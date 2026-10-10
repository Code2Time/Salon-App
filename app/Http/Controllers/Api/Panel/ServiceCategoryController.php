<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\StoreServiceCategoryRequest;
use App\Http\Requests\Panel\UpdateServiceCategoryRequest;
use App\Http\Resources\ServiceCategoryResource;
use App\Models\ServiceCategory;
use App\Services\CurrentSalon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ServiceCategoryController extends Controller
{
    public function index(Request $request, CurrentSalon $context): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'featured' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);
        $query = $context->get()->serviceCategories()->withCount('services')
            ->orderBy('sort_order')->orderBy('name');

        if ($search = $filters['search'] ?? null) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($status = $filters['status'] ?? null) {
            $query->where('is_active', $status === 'active');
        }
        if (array_key_exists('featured', $filters)) {
            $query->where('is_featured', $filters['featured']);
        }

        return ServiceCategoryResource::collection(
            $query->paginate($filters['per_page'] ?? 12)->withQueryString(),
        );
    }

    public function store(StoreServiceCategoryRequest $request, CurrentSalon $context): JsonResponse
    {
        $data = $request->validated();
        $category = $context->get()->serviceCategories()->create([
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'image' => isset($data['image']) ? $data['image']->store('service-categories', 'public') : null,
            'is_featured' => $data['is_featured'] ?? false,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return (new ServiceCategoryResource($category->loadCount('services')))
            ->response()->setStatusCode(201);
    }

    public function show(ServiceCategory $serviceCategory, CurrentSalon $context): ServiceCategoryResource
    {
        return new ServiceCategoryResource(
            $this->category($serviceCategory, $context)
                ->load(['services' => fn ($query) => $query->orderBy('name')])
                ->loadCount('services'),
        );
    }

    public function update(
        UpdateServiceCategoryRequest $request,
        ServiceCategory $serviceCategory,
        CurrentSalon $context,
    ): ServiceCategoryResource {
        $category = $this->category($serviceCategory, $context);
        $data = $request->validated();
        $oldImage = $category->image;
        $newImage = isset($data['image']) ? $data['image']->store('service-categories', 'public') : null;

        $category->update([
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'is_featured' => $data['is_featured'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
            ...($newImage ? ['image' => $newImage] : []),
        ]);

        if ($newImage && $oldImage && ! str_starts_with($oldImage, 'http') && ! str_starts_with($oldImage, '/')) {
            Storage::disk('public')->delete($oldImage);
        }

        return new ServiceCategoryResource($category->loadCount('services'));
    }

    public function deletionImpact(ServiceCategory $serviceCategory, CurrentSalon $context): JsonResponse
    {
        $category = $this->category($serviceCategory, $context);
        $services = $category->services()->orderBy('name')->get()->map(fn ($service) => [
            'id' => $service->id,
            'name' => $service->name,
            'will_be_deleted' => ! $service->categories()->whereKeyNot($category->id)->exists(),
        ]);

        return response()->json(['data' => [
            'category' => ['id' => $category->id, 'name' => $category->name],
            'services' => $services,
        ]]);
    }

    public function destroy(ServiceCategory $serviceCategory, CurrentSalon $context): JsonResponse
    {
        $category = $this->category($serviceCategory, $context);
        $image = $category->image;

        $deletedServices = DB::transaction(function () use ($category) {
            $services = $category->services()->lockForUpdate()->get();
            $category->services()->detach();
            $deleted = [];

            foreach ($services as $service) {
                if (! $service->categories()->exists()) {
                    $service->delete();
                    $deleted[] = $service->name;
                }
            }

            $category->delete();

            return $deleted;
        });

        if ($image && ! str_starts_with($image, 'http') && ! str_starts_with($image, '/')) {
            Storage::disk('public')->delete($image);
        }

        return response()->json([
            'message' => 'دسته‌بندی حذف شد.',
            'deleted_services' => $deletedServices,
        ]);
    }

    private function category(ServiceCategory $category, CurrentSalon $context): ServiceCategory
    {
        return $context->get()->serviceCategories()->whereKey($category->id)->firstOrFail();
    }
}
