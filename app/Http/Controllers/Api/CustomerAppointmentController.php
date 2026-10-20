<?php

namespace App\Http\Controllers\Api;

use App\Enums\AppointmentStatus;
use App\Enums\SalonRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Salon;
use App\Notifications\AppointmentCancelledByCustomer;
use App\Services\Appointments\AppointmentAvailability;
use App\Services\Appointments\BookAppointment;
use App\Services\Appointments\ExpirePastAppointments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerAppointmentController extends Controller
{
    public function availability(Request $request): JsonResponse
    {
        $data = $this->bookingData($request, false);
        $salon = $this->salon($data['salon'] ?? null);

        return response()->json(['data' => [
            'date' => $data['date'],
            'starts' => (new AppointmentAvailability($salon, $data['date'], $data['items']))->availableStarts(),
        ]]);
    }

    public function store(Request $request, BookAppointment $booking): AppointmentResource
    {
        $data = $this->bookingData($request, true);
        $appointment = $booking->create($this->salon($data['salon'] ?? null), $request->user(), $request->user(), $data, 'customer');

        return new AppointmentResource($appointment);
    }

    public function index(Request $request, ExpirePastAppointments $expiration)
    {
        $expiration->handle(customerId: $request->user()->id);

        return AppointmentResource::collection(Appointment::query()->where('customer_id', $request->user()->id)
            ->with(['salon.appointmentSetting', 'items.provider.user'])->orderByDesc('starts_at')->paginate(10));
    }

    public function show(Request $request, Appointment $appointment, ExpirePastAppointments $expiration): AppointmentResource
    {
        abort_unless($appointment->customer_id === $request->user()->id, 404);
        $expiration->handle(customerId: $request->user()->id);
        $appointment->refresh();

        return new AppointmentResource($appointment->load(['salon.appointmentSetting', 'customer', 'items.provider.user']));
    }

    public function cancel(Request $request, Appointment $appointment, ExpirePastAppointments $expiration): JsonResponse
    {
        abort_unless($appointment->customer_id === $request->user()->id, 404);
        $expiration->handle(customerId: $request->user()->id);
        $appointment->refresh();
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        abort_unless($appointment->status === AppointmentStatus::Confirmed, 422, 'این نوبت قابل لغو نیست.');
        $hours = $appointment->salon->appointmentSetting?->cancellation_notice_hours ?? 5;
        if (now()->addHours($hours)->gt($appointment->starts_at)) {
            throw ValidationException::withMessages(['appointment' => "لغو نوبت کمتر از {$hours} ساعت مانده به شروع امکان‌پذیر نیست."]);
        }
        $appointment->update(['status' => AppointmentStatus::Cancelled, 'cancellation_reason' => $data['reason'] ?? null, 'cancelled_by_id' => $request->user()->id, 'cancelled_at' => now()]);
        $appointment->reservationSlots()->delete();
        $appointment->loadMissing(['customer', 'items.provider.user']);

        $owners = $appointment->salon->users()
            ->wherePivot('role', SalonRole::Owner->value)
            ->wherePivot('is_active', true)
            ->where('users.is_active', true)
            ->get();
        $providers = $appointment->items
            ->map(fn ($item) => $item->provider?->user)
            ->filter();

        $owners->concat($providers)->unique('id')->each->notify(
            new AppointmentCancelledByCustomer(
                $appointment->id,
                $appointment->customer->name,
                $appointment->cancellation_reason,
                $appointment->salon_id,
            ),
        );

        return response()->json(['message' => 'نوبت لغو شد.']);
    }

    private function bookingData(Request $request, bool $withStart): array
    {
        return $request->validate([
            'salon' => ['nullable', 'string', 'max:100'], 'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'starts_at' => [$withStart ? 'required' : 'nullable', 'date_format:H:i'], 'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.service_id' => ['required', 'integer'], 'items.*.employee_membership_id' => ['required', 'integer'],
        ]);
    }

    private function salon(?string $slug): Salon
    {
        return Salon::query()->where('is_active', true)->when($slug, fn ($query) => $query->where('slug', $slug))
            ->when(! $slug, fn ($query) => $query->orderByRaw("CASE WHEN slug = 'ara' THEN 0 ELSE 1 END"))->firstOrFail();
    }
}
