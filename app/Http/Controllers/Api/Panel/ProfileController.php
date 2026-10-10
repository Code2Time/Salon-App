<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\UpdateProfileRequest;
use App\Http\Resources\CurrentUserResource;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request): CurrentUserResource
    {
        $user = $request->user();
        $data = $request->validated();
        $oldImage = $user->image;
        $newImage = isset($data['image']) ? $data['image']->store('users', 'public') : null;

        $user->update([
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'name' => trim($data['first_name']).' '.trim($data['last_name']),
            'email' => $data['email'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            ...($newImage ? ['image' => $newImage] : []),
        ]);

        if ($newImage && $oldImage && ! str_starts_with($oldImage, 'http') && ! str_starts_with($oldImage, '/')) {
            Storage::disk('public')->delete($oldImage);
        }

        return new CurrentUserResource($user->load([
            'salons' => fn ($query) => $query->wherePivot('is_active', true)->where('salons.is_active', true),
        ]));
    }
}
