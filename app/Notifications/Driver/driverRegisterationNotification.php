<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class driverRegisterationNotification extends Notification
{
    use Queueable;
    public $driver;
    /**
     * Create a new notification instance.
     */
    public function __construct($driver)
    {
        $this->driver = $driver;
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
            'id' => $this->driver->id,
            'fullname' => ($this->driver->first_name . ' ' . $this->driver->last_name),
            'email' => $this->driver->email,
            'message' => ($this->driver->first_name . ' ' . $this->driver->last_name) . ' send Customer request',
            'url' => '/admin/new-contact-us'
        ];
    }
}