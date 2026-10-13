<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\SalonRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\StoreServiceRequest;
use App\Http\Requests\Panel\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\SalonUser;
use App\Models\Service;
use App\Services\CurrentSalon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(Request $request, CurrentSalon $context): AnonymousResourceCollection
    {
        $salon = $context->get();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'category_id' => ['nullable', 'integer'],
            'provider_membership_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);
        $query = $salon->services()
            ->with(['categories:id,name,is_active,image', 'providers.user:id,name,first_name,last_name,image'])
            ->withCount(['categories', 'providers'])
            ->latest();

        if ($search = $filters['search'] ?? null) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($status = $filters['status'] ?? null) {
            $query->where('is_active', $status === 'active');
        }
        if ($categoryId = $filters['category_id'] ?? null) {
            $query->whereHas('categories', fn ($relation) => $relation
                ->where('service_categories.salon_id', $salon->id)
                ->whereKey($categoryId));
        }
        if ($providerId = $filters['provider_membership_id'] ?? null) {
            $query->whereHas('providers', fn ($relation) => $relation
                ->where('salon_user.salon_id', $salon->id)
                ->whereKey($providerId));
        }

        return ServiceResource::collection(
            $query->paginate($filters['per_page'] ?? 5)->withQueryString(),
        );
    }

    public function options(CurrentSalon $context): JsonResponse
    {
        $salon = $context->get();
        $categories = $salon->serviceCategories()->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'is_active']);
        $providers = SalonUser::query()
            ->where('salon_id', $salon->id)
            ->where('role', SalonRole::Employee->value)
            ->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->with('user:id,name,first_name,last_name,image')
            ->get()
            ->map(fn ($membership) => [
                'membership_id' => $membership->id,
                'user_id' => $membership->user_id,
                'name' => $membership->user->name,
            ]);

        return response()->json(['data' => [
            'categories' => $categories,
            'providers' => $providers,
        ]]);
    }

    public function store(StoreServiceRequest $request, CurrentSalon $context): JsonResponse
    {
        $data = $request->validated();
        $service = DB::transaction(function () use ($data, $context) {
            $service = $context->get()->services()->create([
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'duration_minutes' => $data['duration_minutes'],
                'is_active' => $data['is_active'],
            ]);
            $service->categories()->sync($data['category_ids']);
            $service->providers()->sync($data['provider_membership_ids'] ?? []);

            return $service;
        });

        return (new ServiceResource($this->loaded($service)))->response()->setStatusCode(201);
    }

    public function show(Service $service, CurrentSalon $context): ServiceResource
    {
        return new ServiceResource($this->loaded($this->service($service, $context)));
    }

    public function update(
        UpdateServiceRequest $request,
        Service $service,
        CurrentSalon $context,
    ): ServiceResource {
        $service = $this->service($service, $context);
        $data = $request->validated();

        DB::transaction(function () use ($service, $data) {
            $service->update([
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'duration_minutes' => $data['duration_minutes'],
                'is_active' => $data['is_active'],
            ]);
            $service->categories()->sync($data['category_ids']);
            $service->providers()->sync($data['provider_membership_ids'] ?? []);
        });

        return new ServiceResource($this->loaded($service));
    }

    public function destroy(Service $service, CurrentSalon $context): JsonResponse
    {
        $this->service($service, $context)->delete();

        return response()->json(['message' => 'خدمت حذف شد.']);
    }

    private function service(Service $service, CurrentSalon $context): Service
    {
        return $context->get()->services()->whereKey($service->id)->firstOrFail();
    }

    private function loaded(Service $service): Service
    {
        return $service->load([
            'categories:id,name,is_active,image',
            'providers.user:id,name,first_name,last_name,image',
        ])->loadCount(['categories', 'providers']);
    }
}
