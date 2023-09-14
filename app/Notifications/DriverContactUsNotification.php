<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverContactUsNotification extends Notification
{
    use Queueable;
    public $contactUs;


    /**
     * Create a new notification instance.
     */
    public function __construct($contactUs)
    {
        $this->contactUs = $contactUs;
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
            'id' => $this->contactUs['id'],
            'driver_id'=>$this->contactUs['driver_id'],
            'message' => ($this->contactUs->first_name . ' ' . $this->contactUs->last_name) . ' has query regarding ' . $this->contactUs['needHelpFor'] . '.',
            'url' => '/admin/driver-contact-us'
        ];
    }
}
