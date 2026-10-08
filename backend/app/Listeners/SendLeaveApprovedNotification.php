<?php

namespace App\Listeners;

use App\Events\LeaveApproved;
use App\Notifications\LeaveApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendLeaveApprovedNotification implements ShouldQueue
{
    public function handle(LeaveApproved $event): void
    {
        $leaveRequest = $event->leaveRequest;

        $leaveRequest->loadMissing('employee.user');

        $leaveRequest->employee->user->notify(
            new LeaveApprovedNotification($leaveRequest)
        );
    }
}