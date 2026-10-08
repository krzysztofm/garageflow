<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Events\LeaveApproved;

class LeaveService
{
    public function create(User $user, array $data): LeaveRequest
    {
        Gate::forUser($user)->authorize('create', LeaveRequest::class);

        $employee = $user->employee()->firstOrFail();

        $leaveRequest = $employee->leaveRequests()->make($data);
        $leaveRequest->status = 'pending';
        $leaveRequest->save();

        return $leaveRequest;
    }

    public function review(
        LeaveRequest $leaveRequest,
        User $reviewer,
        string $status
    ): LeaveRequest {
        return DB::transaction(function () use (
            $leaveRequest,
            $reviewer,
            $status
        ) {
            $lockedRequest = LeaveRequest::query()
                ->lockForUpdate()
                ->findOrFail($leaveRequest->id);

            Gate::forUser($reviewer)->authorize(
                'review',
                $lockedRequest
            );

            if ($lockedRequest->status !== 'pending') {
                abort(409, 'This leave request has already been reviewed.');
            }

            $lockedRequest->status = $status;
            $lockedRequest->reviewed_by = $reviewer->id;
            $lockedRequest->reviewed_at = now();
            $lockedRequest->save();

            if ($status === 'approved') {
                LeaveApproved::dispatch($lockedRequest);
            }

            return $lockedRequest;
        });
    }
}