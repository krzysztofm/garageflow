<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'manager_id' => null,
            'department' => 'Workshop',
            'position' => fake()->randomElement([
                'Mechanic',
                'Electrician',
                'Service Advisor',
            ]),
        ];
    }
}