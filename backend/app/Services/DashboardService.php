<?php

namespace App\Services;

use App\Models\BusinessTrip;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function getFor(User $user): array
    {
        $today = now()->toDateString();
        $version = Cache::get('dashboard:version', 0);

        $key = "dashboard:{$user->id}:{$user->role}:{$today}:{$version}";

        return Cache::remember($key, 30, function () use ($user, $today) {
            $employees = Employee::query()->visibleTo($user);

            $employeeIds = (clone $employees)->select('employees.id');

            $leaves = LeaveRequest::query()->whereIn(
                'employee_id',
                clone $employeeIds
            );

            $trips = BusinessTrip::query()->whereIn(
                'employee_id',
                clone $employeeIds
            );

            $absent = (clone $employees)->where(
                function (Builder $query) use ($today) {
                    $query->whereHas(
                        'leaveRequests',
                        fn (Builder $leaves) => $leaves
                            ->where('status', 'approved')
                            ->where('start_date', '<=', $today)
                            ->where('end_date', '>=', $today)
                    )->orWhereHas(
                        'businessTrips',
                        fn (Builder $trips) => $trips
                            ->where('status', 'planned')
                            ->where('start_date', '<=', $today)
                            ->where('end_date', '>=', $today)
                    );
                }
            );

            return [
                'date' => $today,
                'counts' => [
                    'employees' => (clone $employees)->count(),
                    'pending_leaves' => $leaves
                        ->where('status', 'pending')->count(),
                    'planned_trips' => $trips
                        ->where('status', 'planned')->count(),
                    'absent_today' => (clone $absent)->count(),
                ],
                'absent_employees' => $absent
                    ->with('user')
                    ->orderBy('id')
                    ->limit(10)
                    ->get()
                    ->map(fn (Employee $employee) => [
                        'id' => $employee->id,
                        'name' => $employee->user->name,
                        'department' => $employee->department,
                        'position' => $employee->position,
                    ])
                    ->all(),
            ];
        });
    }
}