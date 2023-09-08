<?php

namespace App\Notifications\chef;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class foodItemstatusChangeMail extends Notification
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
        return [
            'food_id' => $this->chefDetail['food_id'],
            'chef_id' => $this->chefDetail['id'],
            'message' => ($this->chefDetail['food_name'] . ' has been approved.'),
            'url' => '/shef/shef-menu'
        ];
    }
}
