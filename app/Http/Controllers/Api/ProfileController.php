<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOwnProfileRequest;
use App\Http\Resources\CurrentUserResource;
use App\Services\Profiles\UpdateOwnProfile;

class ProfileController extends Controller
{
    public function update(UpdateOwnProfileRequest $request, UpdateOwnProfile $updater): CurrentUserResource
    {
        return new CurrentUserResource($updater->handle($request->user(), $request->validated()));
    }
}
