<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Specialist;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    private const APPOINTMENTS_PER_SPECIALIST = 3;

    private const MAX_PLACEMENT_ATTEMPTS = 20;

    public function run(): void
    {
        $services = $this->seedServices();
        $specialists = $this->seedSpecialists($services);
        $this->seedAppointments($specialists, $services);
    }

    private function seedServices(): array
    {
        return [
            'haircut' => Service::query()->firstOrCreate(
                ['name' => 'Haircut'],
                ['duration_minutes' => 50]
            ),
            'hairstyling' => Service::query()->firstOrCreate(
                ['name' => 'Hairstyling'],
                ['duration_minutes' => 70]
            ),
            'manicure' => Service::query()->firstOrCreate(
                ['name' => 'Manicure'],
                ['duration_minutes' => 25]
            ),
        ];
    }

    private function seedSpecialists(array $services): array
    {
        $specialists = [
            'A' => ['haircut', 'hairstyling'],
            'B' => ['haircut', 'manicure'],
            'C' => ['hairstyling', 'manicure'],
        ];

        $result = [];
        foreach ($specialists as $name => $serviceKeys) {
            $specialist = Specialist::query()->firstOrCreate(['name' => "Specialist {$name}"]);
            $serviceIds = array_map(fn ($key) => $services[$key]->id, $serviceKeys);
            $specialist->services()->syncWithoutDetaching($serviceIds);
            $result[$name] = ['model' => $specialist, 'services' => $serviceKeys];
        }

        return $result;
    }

    private function seedAppointments(array $specialists, array $services): void
    {
        $date = Carbon::now()->startOfDay();
        $workStart = Carbon::parse($date->toDateString().' '.config('salon.working_hours.start'));
        $workEnd = Carbon::parse($date->toDateString().' '.config('salon.working_hours.end'));
        $slotStep = config('salon.slot_step_minutes');
        $maxSlots = (int) floor($workStart->diffInMinutes($workEnd) / $slotStep);

        foreach ($specialists as $data) {
            $this->createAppointmentsForSpecialist(
                $data['model'],
                array_map(fn ($key) => $services[$key], $data['services']),
                $workStart,
                $workEnd,
                $slotStep,
                $maxSlots
            );
        }
    }

    private function createAppointmentsForSpecialist(
        Specialist $specialist,
        array $availableServices,
        Carbon $workStart,
        Carbon $workEnd,
        int $slotStep,
        int $maxSlots
    ): void {
        $busyIntervals = [];

        for ($i = 0; $i < self::APPOINTMENTS_PER_SPECIALIST; $i++) {
            $service = $availableServices[array_rand($availableServices)];

            for ($attempt = 0; $attempt < self::MAX_PLACEMENT_ATTEMPTS; $attempt++) {
                $start = $workStart->copy()->addMinutes($slotStep * rand(0, $maxSlots - 1));
                $end = $start->copy()->addMinutes($service->duration_minutes);

                if ($end > $workEnd) {
                    continue;
                }

                if (! $this->hasOverlap($start, $end, $busyIntervals)) {
                    $busyIntervals[] = [$start->copy(), $end->copy()];
                    Appointment::create([
                        'specialist_id' => $specialist->id,
                        'service_id' => $service->id,
                        'start_at' => $start,
                        'end_at' => $end,
                        'canceled' => false,
                    ]);
                    break;
                }
            }
        }
    }

    private function hasOverlap(Carbon $start, Carbon $end, array $busyIntervals): bool
    {
        foreach ($busyIntervals as [$bStart, $bEnd]) {
            if ($start < $bEnd && $end > $bStart) {
                return true;
            }
        }

        return false;
    }
}
