<?php

namespace App\Notifications\Driver;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverSendReviewToAdmin extends Notification
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
    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->driver->id,
            'message' => ucfirst($this->driver->firstName) . ' ' . ucfirst($this->driver->lastName) . ' sent a profile review request on ' . date('d M Y', strtotime(Carbon::now())),
            'url' => '/admin/driver-final-review',
        ];
    }
}
