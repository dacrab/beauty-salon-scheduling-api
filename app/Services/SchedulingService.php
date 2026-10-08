<?php

namespace App\Services;

use App\Exceptions\OutsideWorkingHoursException;
use App\Exceptions\SlotNotAvailableException;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Specialist;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SchedulingService
{
    public function getAvailableSlots(Specialist $specialist, Service $service, Carbon $date): array
    {
        $workStart = $this->workStart($date);
        $workEnd = $this->workEnd($date);
        $slotStep = (int) config('salon.slot_step_minutes');

        $busy = Appointment::forSpecialist($specialist->id)
            ->active()
            ->overlapping($workStart, $workEnd)
            ->orderBy('start_at')
            ->get();

        $slots = [];
        for ($start = $workStart->copy(); $start < $workEnd; $start->addMinutes($slotStep)) {
            $end = $start->copy()->addMinutes($service->duration_minutes);

            if ($end > $workEnd) {
                break;
            }

            if ($this->isFree($start, $end, $busy)) {
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

    public function book(Specialist $specialist, Service $service, Carbon $start): Appointment
    {
        $end = $start->copy()->addMinutes($service->duration_minutes);

        if (! $this->isWithinWorkingHours($start, $end)) {
            throw new OutsideWorkingHoursException;
        }

        if ($this->hasConflict($specialist, $start, $end)) {
            throw new SlotNotAvailableException;
        }

        return Appointment::create([
            'specialist_id' => $specialist->id,
            'service_id' => $service->id,
            'start_at' => $start,
            'end_at' => $end,
            'canceled' => false,
        ]);
    }

    public function cancel(Appointment $appointment): void
    {
        $appointment->update(['canceled' => true]);
    }

    private function isWithinWorkingHours(Carbon $start, Carbon $end): bool
    {
        return $start >= $this->workStart($start) && $end <= $this->workEnd($end);
    }

    private function hasConflict(Specialist $specialist, Carbon $start, Carbon $end): bool
    {
        return Appointment::forSpecialist($specialist->id)
            ->active()
            ->overlapping($start, $end)
            ->exists();
    }

    private function isFree(Carbon $start, Carbon $end, Collection $busy): bool
    {
        foreach ($busy as $appointment) {
            if ($start < $appointment->end_at && $end > $appointment->start_at) {
                return false;
            }
        }

        return true;
    }

    private function workStart(Carbon $date): Carbon
    {
        return Carbon::parse($date->toDateString().' '.config('salon.working_hours.start'));
    }

    private function workEnd(Carbon $date): Carbon
    {
        return Carbon::parse($date->toDateString().' '.config('salon.working_hours.end'));
    }
}
