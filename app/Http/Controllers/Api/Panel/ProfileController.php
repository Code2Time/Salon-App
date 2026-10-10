<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\UpdateProfileRequest;
use App\Http\Resources\CurrentUserResource;
use App\Services\Profiles\UpdateOwnProfile;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request, UpdateOwnProfile $updater): CurrentUserResource
    {
        return new CurrentUserResource($updater->handle($request->user(), $request->validated()));
    }
}
