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

    private const SERVICES = [
        'haircut' => 50,
        'hairstyling' => 70,
        'manicure' => 25,
    ];

    private const SPECIALISTS = [
        'A' => ['haircut', 'hairstyling'],
        'B' => ['haircut', 'manicure'],
        'C' => ['hairstyling', 'manicure'],
    ];

    public function run(): void
    {
        $services = [];

        foreach (self::SERVICES as $name => $duration) {
            $services[$name] = Service::firstOrCreate(
                ['name' => ucfirst($name)],
                ['duration_minutes' => $duration]
            );
        }

        foreach (self::SPECIALISTS as $name => $serviceKeys) {
            $specialist = Specialist::firstOrCreate(['name' => "Specialist {$name}"]);
            $serviceIds = array_map(fn ($key) => $services[$key]->id, $serviceKeys);

            $specialist->services()->syncWithoutDetaching($serviceIds);
            $this->seedAppointments($specialist, $serviceKeys, $services);
        }
    }

    private function seedAppointments(Specialist $specialist, array $serviceKeys, array $services): void
    {
        $workStart = Carbon::today()->setTimeFromTimeString(config('salon.working_hours.start'));
        $workEnd = Carbon::today()->setTimeFromTimeString(config('salon.working_hours.end'));
        $slotStep = (int) config('salon.slot_step_minutes');
        $slotCount = (int) ($workStart->diffInMinutes($workEnd) / $slotStep);

        $booked = [];

        for ($i = 0; $i < self::APPOINTMENTS_PER_SPECIALIST; $i++) {
            $service = $services[$serviceKeys[array_rand($serviceKeys)]];

            for ($attempt = 0; $attempt < self::MAX_PLACEMENT_ATTEMPTS; $attempt++) {
                $start = $workStart->copy()->addMinutes($slotStep * rand(0, $slotCount - 1));
                $end = $start->copy()->addMinutes($service->duration_minutes);

                if ($end > $workEnd || $this->overlaps($start, $end, $booked)) {
                    continue;
                }

                $booked[] = [$start->copy(), $end->copy()];

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

    private function overlaps(Carbon $start, Carbon $end, array $booked): bool
    {
        foreach ($booked as [$bookedStart, $bookedEnd]) {
            if ($start < $bookedEnd && $end > $bookedStart) {
                return true;
            }
        }

        return false;
    }
}
