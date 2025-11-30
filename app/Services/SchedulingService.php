<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Specialist;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SchedulingService
{
    public function getAvailableSlots(Specialist $specialist, Service $service, Carbon $date): array
    {
        $workStart = $this->getWorkStart($date);
        $workEnd = $this->getWorkEnd($date);
        $slotStep = config('salon.slot_step_minutes');

        $busyIntervals = $this->getBusyIntervals($specialist, $workStart, $workEnd);

        $slots = [];
        for ($cursor = $workStart->copy(); $cursor < $workEnd; $cursor->addMinutes($slotStep)) {
            $start = $cursor->copy();
            $end = $start->copy()->addMinutes($service->duration_minutes);

            if ($end > $workEnd) {
                break;
            }

            if (! $this->hasOverlap($start, $end, $busyIntervals)) {
                $slots[] = [
                    'specialist_id' => $specialist->id,
                    'start_time' => $start->toIso8601String(),
                    'end_time' => $end->toIso8601String(),
                ];
            }
        }

        return $slots;
    }

    public function canSpecialistProvideService(Specialist $specialist, Service $service): bool
    {
        return $specialist->services()->whereKey($service->id)->exists();
    }

    public function isWithinWorkingHours(Carbon $start, Carbon $end): bool
    {
        $workStart = $this->getWorkStart($start);
        $workEnd = $this->getWorkEnd($start);

        return $start >= $workStart && $end <= $workEnd;
    }

    public function hasConflict(Specialist $specialist, Carbon $start, Carbon $end): bool
    {
        return Appointment::forSpecialist($specialist->id)
            ->active()
            ->overlapping($start, $end)
            ->exists();
    }

    public function createAppointment(Specialist $specialist, Service $service, Carbon $start): Appointment
    {
        $end = $start->copy()->addMinutes($service->duration_minutes);

        return Appointment::create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'start_at' => $start,
            'end_at' => $end,
            'canceled' => false,
        ]);
    }

    public function cancelAppointment(Appointment $appointment): void
    {
        $appointment->update(['canceled' => true]);
    }

    private function getBusyIntervals(Specialist $specialist, Carbon $workStart, Carbon $workEnd): Collection
    {
        return Appointment::forSpecialist($specialist->id)
            ->active()
            ->overlapping($workStart, $workEnd)
            ->orderBy('start_at')
            ->get()
            ->map(fn (Appointment $a) => [
                Carbon::parse($a->start_at),
                Carbon::parse($a->end_at),
            ]);
    }

    private function hasOverlap(Carbon $start, Carbon $end, Collection $busyIntervals): bool
    {
        foreach ($busyIntervals as [$bStart, $bEnd]) {
            if ($start < $bEnd && $end > $bStart) {
                return true;
            }
        }

        return false;
    }

    private function getWorkStart(Carbon $date): Carbon
    {
        return Carbon::parse($date->toDateString().' '.config('salon.working_hours.start'));
    }

    private function getWorkEnd(Carbon $date): Carbon
    {
        return Carbon::parse($date->toDateString().' '.config('salon.working_hours.end'));
    }
}
