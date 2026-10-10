<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\SalonRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\StoreSalonUserRequest;
use App\Http\Requests\Panel\UpdateSalonUserRequest;
use App\Http\Resources\SalonUserResource;
use App\Models\User;
use App\Services\CurrentSalon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SalonUserController extends Controller
{
    public function index(Request $request, CurrentSalon $context): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(SalonRole::class)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);
        $query = $context->get()->users()->orderBy('users.id', 'desc');

        if ($search = $filters['search'] ?? null) {
            $query->where(function ($query) use ($search) {
                $query->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.mobile', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }
        if ($role = $filters['role'] ?? null) {
            $query->wherePivot('role', $role);
        }
        if ($status = $filters['status'] ?? null) {
            $query->wherePivot('is_active', $status === 'active');
        }

        return SalonUserResource::collection($query->paginate($filters['per_page'] ?? 10)->withQueryString());
    }

    public function store(StoreSalonUserRequest $request, CurrentSalon $context): JsonResponse
    {
        $data = $request->validated();
        $salon = $context->get();
        $existing = User::query()->where('mobile', $data['mobile'])->first();

        if ($existing && ! $existing->is_active) {
            throw ValidationException::withMessages(['mobile' => 'حساب مرتبط با این شماره غیرفعال است.']);
        }
        if ($existing && $salon->users()->whereKey($existing->id)->exists()) {
            throw ValidationException::withMessages(['mobile' => 'این کاربر از قبل عضو سالن است.']);
        }

        $user = DB::transaction(function () use ($data, $salon, $existing) {
            $image = isset($data['image']) ? $data['image']->store('users', 'public') : null;
            $user = $existing ?? User::create([
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'name' => trim($data['first_name']).' '.trim($data['last_name']),
                'mobile' => $data['mobile'],
                'email' => $data['email'] ?? null,
                'is_active' => true,
                'image' => $image,
            ]);

            if ($existing && $image) {
                $existing->update(['image' => $image]);
            }

            $salon->users()->attach($user->id, [
                'role' => $data['role'],
                'permissions' => $data['role'] === SalonRole::Employee->value
                    ? $this->normalizedPermissions($data['permissions'] ?? ['dashboard', 'appointments'])
                    : null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $user;
        });

        return (new SalonUserResource($salon->users()->whereKey($user->id)->firstOrFail()))
            ->response()->setStatusCode(201);
    }

    public function show(User $user, CurrentSalon $context): SalonUserResource
    {
        return new SalonUserResource($this->member($user, $context));
    }

    public function update(UpdateSalonUserRequest $request, User $user, CurrentSalon $context): SalonUserResource
    {
        $member = $this->member($user, $context);
        $data = $request->validated();
        $this->protectLastOwner($member, $data['role'], $data['is_active'], $context);
        $oldImage = $member->image;
        $newImage = isset($data['image']) ? $data['image']->store('users', 'public') : null;

        DB::transaction(function () use ($member, $data, $context, $newImage) {
            $member->update([
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'name' => trim($data['first_name']).' '.trim($data['last_name']),
                'email' => $data['email'] ?? null,
                ...($newImage ? ['image' => $newImage] : []),
            ]);
            $context->get()->users()->updateExistingPivot($member->id, [
                'role' => $data['role'],
                'permissions' => $data['role'] === SalonRole::Employee->value
                    ? $this->normalizedPermissions($data['permissions'] ?? [])
                    : null,
                'is_active' => $data['is_active'],
            ]);
        });

        if ($newImage && $oldImage && ! str_starts_with($oldImage, 'http') && ! str_starts_with($oldImage, '/')) {
            Storage::disk('public')->delete($oldImage);
        }

        return new SalonUserResource($context->get()->users()->whereKey($member->id)->firstOrFail());
    }

    public function destroy(User $user, CurrentSalon $context): JsonResponse
    {
        $member = $this->member($user, $context);
        $this->protectLastOwner($member, (string) $member->pivot->role->value, false, $context);

        if ($member->salons()->where('salons.id', '!=', $context->get()->id)->exists()) {
            throw ValidationException::withMessages([
                'user' => 'این حساب در سالن دیگری نیز عضویت دارد و حذف سراسری آن مجاز نیست.',
            ]);
        }

        $member->delete();

        return response()->json(['message' => 'کاربر حذف شد.']);
    }

    private function member(User $user, CurrentSalon $context): User
    {
        return $context->get()->users()->whereKey($user->id)->firstOrFail();
    }

    private function protectLastOwner(User $member, string $nextRole, bool $nextActive, CurrentSalon $context): void
    {
        $isActiveOwner = $member->pivot->role === SalonRole::Owner && $member->pivot->is_active;
        if (! $isActiveOwner || ($nextRole === SalonRole::Owner->value && $nextActive)) {
            return;
        }

        $owners = $context->get()->users()
            ->wherePivot('role', SalonRole::Owner->value)
            ->wherePivot('is_active', true)
            ->count();
        if ($owners <= 1) {
            throw ValidationException::withMessages(['role' => 'آخرین مالک فعال سالن قابل غیرفعال‌سازی یا تغییر نقش نیست.']);
        }
    }

    /** @param array<int, string> $permissions */
    private function normalizedPermissions(array $permissions): array
    {
        if (in_array('manage_service_categories', $permissions, true)
            && ! in_array('service_categories', $permissions, true)) {
            $permissions[] = 'service_categories';
        }

        return array_values(array_unique($permissions));
    }
}
