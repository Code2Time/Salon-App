<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\UpdateSalonSettingsRequest;
use App\Http\Resources\SalonResource;
use App\Services\CurrentSalon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SalonSettingsController extends Controller
{
    public function show(CurrentSalon $context): SalonResource
    {
        return new SalonResource($context->get());
    }

    public function update(UpdateSalonSettingsRequest $request, CurrentSalon $context): SalonResource
    {
        $salon = $context->get();
        $data = $request->validated();
        $oldLogo = $salon->logo;
        $newLogo = $request->file('logo')?->store('salons/'.$salon->id, 'public');
        unset($data['logo'], $data['remove_logo']);
        if ($newLogo || $request->boolean('remove_logo')) {
            $data['logo'] = $newLogo;
        }
        try {
            $salon->update($data);
        } catch (Throwable $exception) {
            if ($newLogo) {
                Storage::disk('public')->delete($newLogo);
            }
            throw $exception;
        }
        if (array_key_exists('logo', $data) && $oldLogo && $oldLogo !== $newLogo) {
            Storage::disk('public')->delete($oldLogo);
        }

        return new SalonResource($salon->refresh());
    }
}
