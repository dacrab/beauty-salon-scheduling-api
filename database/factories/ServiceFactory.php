<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Haircut', 'Hairstyling', 'Manicure', 'Pedicure', 'Facial']),
            'duration_minutes' => fake()->randomElement([25, 30, 45, 50, 60, 70, 90]),
        ];
    }

    public function haircut(): static
    {
        return $this->state(['name' => 'Haircut', 'duration_minutes' => 50]);
    }

    public function hairstyling(): static
    {
        return $this->state(['name' => 'Hairstyling', 'duration_minutes' => 70]);
    }

    public function manicure(): static
    {
        return $this->state(['name' => 'Manicure', 'duration_minutes' => 25]);
    }
}
