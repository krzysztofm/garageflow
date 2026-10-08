<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Employee $employee): bool
    {
        return $employee->user_id === $user->id
            || (
                $user->isManager()
                && $employee->manager_id === $user->id
            );
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->isManager()
            && $employee->manager_id === $user->id
            && $employee->user_id !== $user->id;
    }
}