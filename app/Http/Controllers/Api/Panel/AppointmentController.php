<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\AppointmentStatus;
use App\Enums\SalonRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Notifications\AppointmentChanged;
use App\Services\Appointments\AppointmentAvailability;
use App\Services\Appointments\BookAppointment;
use App\Services\Appointments\RescheduleAppointment;
use App\Services\CurrentSalon;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function options(CurrentSalon $context): JsonResponse
    {
        $salon = $context->get();
        $customers = $salon->users()->wherePivot('role', SalonRole::Customer->value)->wherePivot('is_active', true)
            ->get(['users.id', 'users.name', 'users.mobile'])->map(fn ($user) => ['id' => $user->id, 'name' => $user->name, 'mobile' => $user->mobile]);
        $services = $salon->services()->where('is_active', true)
            ->with(['providers' => fn ($query) => $query->where('salon_user.is_active', true)->with('user:id,name')])->get()
            ->map(fn ($service) => [
                'id' => $service->id, 'name' => $service->name, 'duration_minutes' => $service->duration_minutes, 'price' => $service->price,
                'providers' => $service->providers->map(fn ($provider) => ['membership_id' => $provider->id, 'name' => $provider->user->name])->values(),
            ]);

        return response()->json(['data' => ['customers' => $customers, 'services' => $services]]);
    }

    public function index(Request $request, CurrentSalon $context)
    {
        $filters = $request->validate(['date' => ['nullable', 'date_format:Y-m-d'], 'status' => ['nullable', Rule::enum(AppointmentStatus::class)]]);
        $query = $context->get()->appointments()->with(['salon.appointmentSetting', 'customer', 'items.provider.user'])->orderByDesc('starts_at');
        if ($filters['date'] ?? null) {
            $timezone = $context->get()->appointmentSetting?->timezone ?? 'Asia/Tehran';
            $start = CarbonImmutable::createFromFormat('Y-m-d H:i', $filters['date'].' 00:00', $timezone)->utc();
            $query->where('starts_at', '>=', $start)->where('starts_at', '<', $start->addDay());
        }
        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }

        return AppointmentResource::collection($query->paginate(10));
    }

    public function availability(Request $request, CurrentSalon $context): JsonResponse
    {
        $data = $this->data($request, false);

        return response()->json(['data' => ['date' => $data['date'], 'starts' => (new AppointmentAvailability($context->get(), $data['date'], $data['items']))->availableStarts()]]);
    }

    public function store(Request $request, CurrentSalon $context, BookAppointment $booking): AppointmentResource
    {
        $data = $this->data($request, true, true);
        $customer = $context->get()->users()->whereKey($data['customer_id'])->wherePivot('role', SalonRole::Customer->value)->firstOrFail();

        return new AppointmentResource($booking->create($context->get(), $customer, $request->user(), $data, 'panel'));
    }

    public function show(Appointment $appointment, CurrentSalon $context): AppointmentResource
    {
        $appointment = $context->get()->appointments()->whereKey($appointment->id)->firstOrFail();

        return new AppointmentResource($appointment->load(['salon.appointmentSetting', 'customer', 'items.provider.user']));
    }

    public function status(Request $request, Appointment $appointment, CurrentSalon $context): AppointmentResource
    {
        $appointment = $context->get()->appointments()->whereKey($appointment->id)->firstOrFail();
        $data = $request->validate(['status' => ['required', Rule::in(['completed', 'no_show', 'cancelled'])], 'reason' => ['nullable', 'string', 'max:1000']]);
        abort_unless($appointment->status === AppointmentStatus::Confirmed, 422, 'تغییر وضعیت از حالت فعلی مجاز نیست.');
        $appointment->update(['status' => $data['status'], ...($data['status'] === 'cancelled' ? ['cancellation_reason' => $data['reason'] ?? null, 'cancelled_by_id' => $request->user()->id, 'cancelled_at' => now()] : [])]);
        if ($data['status'] === 'cancelled') {
            $appointment->reservationSlots()->delete();
            $appointment->customer->notify(new AppointmentChanged('نوبت شما لغو شد', $data['reason'] ?? 'نوبت شما توسط سالن لغو شد.', $appointment->id));
        }

        return new AppointmentResource($appointment->load(['salon.appointmentSetting', 'customer', 'items.provider.user']));
    }

    public function reschedule(Request $request, Appointment $appointment, CurrentSalon $context, RescheduleAppointment $service): AppointmentResource
    {
        $appointment = $context->get()->appointments()->whereKey($appointment->id)->where('status', AppointmentStatus::Confirmed->value)->with(['salon', 'customer'])->firstOrFail();
        $data = $this->data($request, true);
        $updated = $service->handle($appointment, $data);
        $updated->customer->notify(new AppointmentChanged('زمان نوبت شما تغییر کرد', 'زمان یا متخصص یکی از سرویس‌های نوبت شما توسط سالن تغییر کرد.', $updated->id));

        return new AppointmentResource($updated);
    }

    private function data(Request $request, bool $withStart, bool $withCustomer = false): array
    {
        return $request->validate([
            'customer_id' => [$withCustomer ? 'required' : 'nullable', 'integer'], 'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'starts_at' => [$withStart ? 'required' : 'nullable', 'date_format:H:i'], 'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:10'], 'items.*.service_id' => ['required', 'integer'],
            'items.*.employee_membership_id' => ['required', 'integer'],
        ]);
    }
}
