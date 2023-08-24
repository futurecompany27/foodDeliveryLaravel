<?php

namespace App\Notifications\Chef;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChefScheduleCallNotification extends Notification
{
    use Queueable;

    public $ScheduleCall;
    /**
     * Create a new notification instance.
     *
     * @return void
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
    public function toArray($notifiable)
    {
        return [
            'id' => $this->ScheduleCall['id'],
            'chef_id' => $this->ScheduleCall['chef']->chef_id,
            'message' => ($this->ScheduleCall['chef']->first_name . ' ' . $this->ScheduleCall['chef']->first_name) . ' has requested for call on ' . date('d M Y', strtotime($this->ScheduleCall['date'])) . ' between ' . $this->ScheduleCall['slot'] . '.',
            'url' => '/admin/shef-call-request'
        ];
    }
}