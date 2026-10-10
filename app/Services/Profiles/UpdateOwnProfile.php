<?php

namespace App\Services\Profiles;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateOwnProfile
{
    /**
     * @param  array{first_name: string, last_name: string, email?: ?string, birth_date?: ?string, image?: UploadedFile}  $data
     */
    public function handle(User $user, array $data): User
    {
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

        return $user->load([
            'salons' => fn ($query) => $query->wherePivot('is_active', true)->where('salons.is_active', true),
        ]);
    }
}
