<?php

namespace Database\Seeders;

use App\Models\BusinessTrip;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $users = [];

            $accounts = [
                'admin' => ['Admin GarageFlow', 'Office', 'Owner'],
                'manager' => ['Anna Kowalska', 'Workshop', 'Workshop Manager'],
                'employee' => ['Jan Nowak', 'Workshop', 'Mechanic'],
            ];

            foreach ($accounts as $role => [$name, $department, $position]) {
                $users[$role] = User::firstOrCreate(
                    ['email' => "{$role}@garageflow.test"],
                    [
                        'name' => $name,
                        'role' => $role,
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                    ],
                );
            }

            $profiles = [];

            foreach ($accounts as $role => [$name, $department, $position]) {
                $managerId = match ($role) {
                    'manager' => $users['admin']->id,
                    'employee' => $users['manager']->id,
                    default => null,
                };

                $profiles[$role] = Employee::firstOrCreate(
                    ['user_id' => $users[$role]->id],
                    [
                        'manager_id' => $managerId,
                        'department' => $department,
                        'position' => $position,
                    ],
                );
            }

            $employee = $profiles['employee'];

            if (! $employee->leaveRequests()->exists()) {
                LeaveRequest::factory()->for($employee)->create([
                    'start_date' => now()->addDays(7)->toDateString(),
                    'end_date' => now()->addDays(9)->toDateString(),
                    'reason' => 'Family holiday',
                    'status' => 'pending',
                ]);

                LeaveRequest::factory()->for($employee)->create([
                    'start_date' => now()->subDay()->toDateString(),
                    'end_date' => now()->addDay()->toDateString(),
                    'reason' => 'Personal matters',
                    'status' => 'approved',
                    'reviewed_by' => $users['manager']->id,
                    'reviewed_at' => now()->subDays(3),
                ]);
            }

            if (! $employee->businessTrips()->exists()) {
                BusinessTrip::factory()->for($employee)->create([
                    'start_date' => now()->addDays(14)->toDateString(),
                    'end_date' => now()->addDays(15)->toDateString(),
                    'destination' => 'Warsaw',
                    'description' => 'Technical training',
                    'status' => 'planned',
                ]);
            }
        });
    }
}