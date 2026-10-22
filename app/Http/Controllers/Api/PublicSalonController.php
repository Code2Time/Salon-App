<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalonResource;
use App\Models\Salon;
use Illuminate\Http\Request;

class PublicSalonController extends Controller
{
    public function __invoke(Request $request): SalonResource
    {
        $data = $request->validate(['salon' => ['nullable', 'string', 'max:100']]);
        $query = Salon::query()->where('is_active', true);
        $salon = isset($data['salon'])
            ? $query->where('slug', $data['salon'])->firstOrFail()
            : ($query->where('slug', 'ara')->first() ?? Salon::where('is_active', true)->orderBy('id')->firstOrFail());

        return new SalonResource($salon);
    }
}
