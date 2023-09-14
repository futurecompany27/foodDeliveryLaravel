<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverScheduleCallNotification extends Notification
{
    use Queueable;

    public $ScheduleCall;
    /**
     * Create a new notification instance.
     */
    public function __construct($ScheduleCall)
    {
        $this->ScheduleCall = $ScheduleCall;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->ScheduleCall['id'],
            'driver_id' => $this->ScheduleCall['driver_id']->driver_id,
            'message' => ($this->ScheduleCall['driver']->first_name . ' ' . $this->ScheduleCall['driver']->last_name) . ' has requested for call on ' . date('d M Y', strtotime($this->ScheduleCall['date'])) . ' between ' . $this->ScheduleCall['slot'] . '.',
            'url' => '/admin/shef-call-request'
        ];
    }
}
