<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Specialist;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $start = Carbon::today()->setHour(fake()->numberBetween(9, 16))->setMinute(fake()->randomElement([0, 30]));
        $duration = fake()->randomElement([25, 50, 70]);

        return [
            'specialist_id' => Specialist::factory(),
            'service_id' => Service::factory(),
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes($duration),
            'canceled' => false,
        ];
    }

    public function canceled(): static
    {
        return $this->state(['canceled' => true]);
    }

    public function at(Carbon $start, int $durationMinutes): static
    {
        return $this->state([
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes($durationMinutes),
        ]);
    }
}
