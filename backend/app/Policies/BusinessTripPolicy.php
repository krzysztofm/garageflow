<?php

namespace App\Policies;

use App\Models\BusinessTrip;
use App\Models\User;

class BusinessTripPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BusinessTrip $businessTrip): bool
    {
        $employee = $businessTrip->employee;

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

    public function update(User $user, BusinessTrip $businessTrip): bool
    {
        return $this->view($user, $businessTrip);
    }
}