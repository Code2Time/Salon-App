<?php

namespace App\Services\Aftercare;

use App\Enums\AppointmentStatus;
use App\Models\AftercareRecommendation;
use App\Models\Appointment;
use App\Models\Product;
use App\Models\User;
use App\Notifications\AftercareProductsSuggested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompleteAppointment
{
    public function handle(Appointment $appointment, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor) {
            $appointment = Appointment::query()->visibleTo($actor, $appointment->salon_id)->lockForUpdate()->findOrFail($appointment->id);
            // Retrying a successful completion does not issue new offers or notifications.
            if ($appointment->status === AppointmentStatus::Completed) {
                return $appointment;
            }
            $allowed = $appointment->status === AppointmentStatus::Confirmed
                || ($appointment->status === AppointmentStatus::Expired && $actor->canViewAllAppointmentsIn($appointment->salon_id));
            abort_unless($allowed, 422, 'ثبت انجام خدمت از وضعیت فعلی مجاز نیست.');
            $appointment->update(['status' => AppointmentStatus::Completed]);
            $appointment->load(['items', 'salon', 'customer']);
            $serviceNames = $appointment->items->pluck('service_name', 'service_id');
            $serviceIds = $appointment->items->pluck('service_id')->filter()->unique();
            $products = Product::query()->where('salon_id', $appointment->salon_id)->where('is_active', true)->where('stock', '>', 0)
                ->whereHas('services', fn ($services) => $services->whereIn('services.id', $serviceIds)->where('product_service.recommendation_enabled', true))
                ->with(['services' => fn ($services) => $services->whereIn('services.id', $serviceIds)->where('product_service.recommendation_enabled', true)])->get();
            $created = false;
            foreach ($products as $product) {
                $percent = $product->services->max(fn ($service) => $service->pivot->discount_enabled ? (int) $service->pivot->discount_percent : 0) ?? 0;
                $recommendation = AftercareRecommendation::firstOrCreate(
                    ['appointment_id' => $appointment->id, 'product_id' => $product->id],
                    [
                        'salon_id' => $appointment->salon_id, 'customer_id' => $appointment->customer_id,
                        'care_instructions' => $product->services->map(fn ($service) => [
                            'service_name' => $serviceNames->get($service->id, $service->name),
                            'text' => $service->pivot->recommendation_text ?? '',
                        ])->values()->all(),
                        'discount_percent' => $percent,
                        'discount_code' => $percent > 0 ? 'CARE-'.Str::upper(Str::random(16)) : null,
                        'expires_at' => $percent > 0 ? now()->addDays($appointment->salon->aftercare_discount_validity_days) : null,
                    ],
                );
                $created = $created || $recommendation->wasRecentlyCreated;
            }
            if ($created) {
                $appointment->customer->notify(new AftercareProductsSuggested($appointment->salon_id, $appointment->id));
            }

            return $appointment;
        });
    }
}
