<?php

namespace App\Notifications\admin;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverRequestQueryNotification extends Notification
{
    use Queueable;

    public $driverRequestQuery;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($driverRequestQuery)
    {
        $this->driverRequestQuery = $driverRequestQuery;
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
            // 'type' => $this->driverRequestQuery->type,
            'id' => $this->driverRequestQuery->driver_id,
            'request_for' => $this->driverRequestQuery->request_for,
            'message' => ($this->driverRequestQuery->driver['firstName'].' '. $this->driverRequestQuery->chef['lastName'] .' has submitted a request to update their information in the driver panel on. ' . date('d M Y', strtotime(Carbon::now())) . '.'),
            'url' => '/admin/driver-change-request'
        ];
    }
}
