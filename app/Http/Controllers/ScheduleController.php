<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookAppointmentRequest;
use App\Http\Requests\ListSlotsRequest;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Specialist;
use App\Services\SchedulingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ScheduleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(\App\Http\Middleware\BearerTokenAuth::class),
        ];
    }

    public function __construct(
        private readonly SchedulingService $schedulingService
    ) {}

    public function listSlots(ListSlotsRequest $request): JsonResponse
    {
        $service = Service::findOrFail($request->validated('service_id'));
        $specialist = Specialist::findOrFail($request->validated('specialist_id'));

        if (! $this->schedulingService->canSpecialistProvideService($specialist, $service)) {
            return response()->json([
                'data' => [],
                'message' => 'Selected specialist does not provide this service',
            ]);
        }

        $date = Carbon::parse($request->validated('date'))->startOfDay();
        $slots = $this->schedulingService->getAvailableSlots($specialist, $service, $date);

        return response()->json(['data' => $slots]);
    }

    public function book(BookAppointmentRequest $request): JsonResponse
    {
        $service = Service::findOrFail($request->validated('service_id'));
        $specialist = Specialist::findOrFail($request->validated('specialist_id'));

        if (! $this->schedulingService->canSpecialistProvideService($specialist, $service)) {
            return response()->json(['message' => 'Specialist does not provide this service'], 422);
        }

        $date = Carbon::parse($request->validated('date'))->toDateString();
        $start = Carbon::parse($date.' '.$request->validated('start_time'));
        $end = $start->copy()->addMinutes($service->duration_minutes);

        if (! $this->schedulingService->isWithinWorkingHours($start, $end)) {
            return response()->json(['message' => 'Outside working hours'], 422);
        }

        if ($this->schedulingService->hasConflict($specialist, $start, $end)) {
            return response()->json(['message' => 'Slot no longer available'], 409);
        }

        $appointment = $this->schedulingService->createAppointment($specialist, $service, $start);

        return response()->json(['data' => $appointment], 201);
    }

    public function cancel(Appointment $appointment): JsonResponse
    {
        $this->schedulingService->cancelAppointment($appointment);

        return response()->json(['message' => 'Canceled']);
    }
}
