<?php

namespace App\Contracts;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Specialist;
use Carbon\Carbon;

interface SchedulingServiceInterface
{
    public function getAvailableSlots(Specialist $specialist, Service $service, Carbon $date): array;

    public function canSpecialistProvideService(Specialist $specialist, Service $service): bool;

    public function isWithinWorkingHours(Carbon $start, Carbon $end): bool;

    public function hasConflict(Specialist $specialist, Carbon $start, Carbon $end): bool;

    public function createAppointment(Specialist $specialist, Service $service, Carbon $start): Appointment;

    public function cancelAppointment(Appointment $appointment): void;
}
