<?php

namespace App\Notifications\Chef;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChefRegisterationNotification extends Notification
{
    use Queueable;
    public $chefDetail;

    /**
     * Create a new notification instance.
     */
    public function __construct($chefDetail)
    {
        $this->chefDetail = $chefDetail;
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
        return  [
            'id' => $this->chefDetail['id'],
            'firstName' => $this->chefDetail['firstName'],
            'lastName' => $this->chefDetail['lastName'],
            'message' => ($this->chefDetail['firstName'] . ' ' . $this->chefDetail['lastName']) . ' register as a new chef.',
            'url' => '/admin/shef-profile'
        ];
    }
}
