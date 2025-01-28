<?php

namespace App\Notifications\Driver;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverSuggestionNotification extends Notification
{
    use Queueable;

    protected $driverDetail;


    /**
     * Create a new notification instance.
     */
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
        return [
            'id' => $this->driverDetail['id'],
            'firstName' => $this->driverDetail['firstName'],
            'lastName' => $this->driverDetail['lastName'],
            'message' => ($this->driverDetail['firstName'] .' ' .$this->driverDetail['lastName'].' has given suggestion.'),
            'url' => '/admin/driver-suggestion'
        ];
    }
}
