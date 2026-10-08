<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveApprovedNotification extends Notification
{
    public function __construct(
        public LeaveRequest $leaveRequest
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $start = $this->leaveRequest->start_date->format('Y-m-d');
        $end = $this->leaveRequest->end_date->format('Y-m-d');

        return (new MailMessage)
            ->subject('GarageFlow: leave approved')
            ->greeting("Hello {$notifiable->name}!")
            ->line('Your leave request has been approved.')
            ->line("Dates: {$start} — {$end}")
            ->salutation('GarageFlow');
    }
}