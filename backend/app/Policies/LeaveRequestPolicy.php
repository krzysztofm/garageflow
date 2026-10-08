<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        $employee = $leaveRequest->employee;

        return $user->isAdmin()
            || $employee->user_id === $user->id
            || (
                $user->isManager()
                && $employee->manager_id === $user->id
            );
    }

    public function create(User $user): bool
    {
        return $user->employee()->exists();
    }

    public function review(User $user, LeaveRequest $leaveRequest): bool
    {
        $employee = $leaveRequest->employee;

        if ($employee->user_id === $user->id) {
            return false;
        }

        return $user->isAdmin()
            || (
                $user->isManager()
                && $employee->manager_id === $user->id
            );
    }
}