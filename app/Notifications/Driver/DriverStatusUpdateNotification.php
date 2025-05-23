<?php

namespace App\Notifications\Driver;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverStatusUpdateNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $driverDetail;

    public function __construct($driverDetail)
    {
        $this->driverDetail = $driverDetail;
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
        $today_date = Carbon::now()->format('d-M-Y h:m:i');
        $status_array = [
            0 => 'You account has been deactivated on ' . $today_date,
            1 => 'Congratulations! your account is activated now.',
            2 => 'Congratulation, your account is in review now'
        ];
        return [
            'id' => $this->driverDetail['id'],
            'firstName' => $this->driverDetail['firstName'],
            'lastName' => $this->driverDetail['lastName'],
            'status' => $this->driverDetail['status'],
            'message' => $status_array[$this->driverDetail['status']],
            'url' => '/delivery/driver-profile'
        ];
    }


}
