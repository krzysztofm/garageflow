<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRequestFactory extends Factory
{
    public function definition(): array
    {
        $startDate = now()->addDays(fake()->numberBetween(1, 30));

        return [
            'employee_id' => Employee::factory(),
            'start_date' => $startDate->toDateString(),
            'end_date' => (clone $startDate)->addDays(3)->toDateString(),
            'reason' => fake()->sentence(),
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }
}