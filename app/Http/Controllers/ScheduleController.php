<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Specialist;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    private const WORK_START = '09:00';
    private const WORK_END = '18:00';
    private const SLOT_STEP_MINUTES = 30; // configurable step size for slot starts

    public function listSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'specialist_id' => ['required', 'integer', 'exists:specialists,id'],
        ]);

        $service = Service::findOrFail($validated['service_id']);
        $specialist = Specialist::findOrFail($validated['specialist_id']);

        if (!$specialist->services()->whereKey($service->id)->exists()) {
            return response()->json([
                'data' => [],
                'message' => 'Selected specialist does not provide this service',
            ], 200);
        }

        $date = Carbon::parse($validated['date'])->startOfDay();
        $workStart = Carbon::parse($date->toDateString().' '.self::WORK_START);
        $workEnd = Carbon::parse($date->toDateString().' '.self::WORK_END);

        $appointments = Appointment::where('specialist_id', $specialist->id)
            ->where('canceled', false)
            ->where(function ($q) use ($workStart, $workEnd) {
                $q->where('start_at', '<', $workEnd)
                  ->where('end_at', '>', $workStart);
            })
            ->orderBy('start_at')
            ->get();

        $busyIntervals = $appointments->map(fn ($a) => [
            Carbon::parse($a->start_at),
            Carbon::parse($a->end_at),
        ])->values();

        $slots = [];
        for ($cursor = $workStart->copy(); $cursor < $workEnd; $cursor->addMinutes(self::SLOT_STEP_MINUTES)) {
            $start = $cursor->copy();
            $end = $start->copy()->addMinutes($service->duration_minutes);
            if ($end > $workEnd) {
                break;
            }

            $overlaps = false;
            foreach ($busyIntervals as [$bStart, $bEnd]) {
                if ($start < $bEnd && $end > $bStart) {
                    $overlaps = true;
                    break;
                }
            }

            if (!$overlaps) {
                $slots[] = [
                    'specialist_id' => $specialist->id,
                    'start_time' => $start->toIso8601String(),
                    'end_time' => $end->toIso8601String(),
                ];
            }
        }

        return response()->json(['data' => $slots]);
    }

    public function book(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'specialist_id' => ['required', 'integer', 'exists:specialists,id'],
            'start_time' => ['required', 'date_format:H:i'],
        ]);

        $service = Service::findOrFail($validated['service_id']);
        $specialist = Specialist::findOrFail($validated['specialist_id']);
        if (!$specialist->services()->whereKey($service->id)->exists()) {
            return response()->json(['message' => 'Specialist does not provide this service'], 422);
        }

        $date = Carbon::parse($validated['date'])->toDateString();
        $start = Carbon::parse($date.' '.$validated['start_time']);
        $end = $start->copy()->addMinutes($service->duration_minutes);

        $workStart = Carbon::parse($date.' '.self::WORK_START);
        $workEnd = Carbon::parse($date.' '.self::WORK_END);
        if ($start < $workStart || $end > $workEnd) {
            return response()->json(['message' => 'Outside working hours'], 422);
        }

        $conflict = Appointment::where('specialist_id', $specialist->id)
            ->where('canceled', false)
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($q) use ($start, $end) {
                    $q->where('start_at', '<', $end)
                        ->where('end_at', '>', $start);
                });
            })
            ->exists();

        if ($conflict) {
            return response()->json(['message' => 'Slot no longer available'], 409);
        }

        $appointment = Appointment::create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'start_at' => $start,
            'end_at' => $end,
            'canceled' => false,
        ]);

        return response()->json(['data' => $appointment], 201);
    }

    public function cancel(Request $request, $appointment): JsonResponse
    {
        $model = Appointment::findOrFail($appointment);
        $model->canceled = true;
        $model->save();
        return response()->json(['message' => 'Canceled']);
    }
}


