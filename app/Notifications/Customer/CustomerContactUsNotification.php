<?php

namespace App\Notifications\Customer;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerContactUsNotification extends Notification
{
    use Queueable;

    public $contactUSDetails;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($contactUSDetails)
    {
        $this->contactUSDetails = $contactUSDetails;
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
            'id' => $this->contactUSDetails->id,
            'fullname' => $this->contactUSDetails->full_name,
            'email' => $this->contactUSDetails->email,
            'message' => $this->contactUSDetails->full_name . ' send Customer request',
            'url' => '/admin/new-contact-us'
        ];
    }
}