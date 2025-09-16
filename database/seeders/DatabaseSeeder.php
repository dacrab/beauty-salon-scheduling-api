<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Specialist;
use App\Models\Service;
use App\Models\Appointment;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed services
        $haircut = Service::query()->firstOrCreate(['name' => 'Haircut'], ['duration_minutes' => 50]);
        $hairstyling = Service::query()->firstOrCreate(['name' => 'Hairstyling'], ['duration_minutes' => 70]);
        $manicure = Service::query()->firstOrCreate(['name' => 'Manicure'], ['duration_minutes' => 25]);

        // Seed specialists
        $specA = Specialist::query()->firstOrCreate(['name' => 'Specialist A']);
        $specB = Specialist::query()->firstOrCreate(['name' => 'Specialist B']);
        $specC = Specialist::query()->firstOrCreate(['name' => 'Specialist C']);

        // Attach service capabilities
        $specA->services()->syncWithoutDetaching([$haircut->id, $hairstyling->id]);
        $specB->services()->syncWithoutDetaching([$haircut->id, $manicure->id]);
        $specC->services()->syncWithoutDetaching([$hairstyling->id, $manicure->id]);

        // Seed random appointments per specialist (3 each) within working hours, non-overlapping
        $date = Carbon::now()->startOfDay();
        $workStart = Carbon::parse($date->toDateString().' 09:00');
        $workEnd = Carbon::parse($date->toDateString().' 18:00');

        foreach ([
            $specA->id => [$haircut, $hairstyling],
            $specB->id => [$haircut, $manicure],
            $specC->id => [$hairstyling, $manicure],
        ] as $specId => $services) {
            $busy = [];
            for ($i = 0; $i < 3; $i++) {
                // pick random service
                $service = $services[array_rand($services)];

                // try a few times to place a non-overlapping appointment
                for ($attempt = 0; $attempt < 20; $attempt++) {
                    $startCandidate = $workStart->copy()->addMinutes(30 * rand(0, 16));
                    $endCandidate = $startCandidate->copy()->addMinutes($service->duration_minutes);
                    if ($endCandidate > $workEnd) {
                        continue;
                    }
                    $overlap = false;
                    foreach ($busy as [$bStart, $bEnd]) {
                        if ($startCandidate < $bEnd && $endCandidate > $bStart) {
                            $overlap = true;
                            break;
                        }
                    }
                    if (!$overlap) {
                        $busy[] = [$startCandidate->copy(), $endCandidate->copy()];
                        Appointment::create([
                            'specialist_id' => $specId,
                            'service_id' => $service->id,
                            'start_at' => $startCandidate,
                            'end_at' => $endCandidate,
                            'canceled' => false,
                        ]);
                        break;
                    }
                }
            }
        }
    }
}
