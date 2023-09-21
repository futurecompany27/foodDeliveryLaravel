<?php

namespace App\Notifications\admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class requestForChefReviewDelete extends Notification
{
    use Queueable;
    public $chefReviewDeleteRequest;
    /**
     * Create a new notification instance.
     */
    public function __construct($chefReviewDeleteRequest)
    {
        $this->chefReviewDeleteRequest = $chefReviewDeleteRequest;
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
            'id' => $this->chefReviewDeleteRequest->chef_id,
            'message' => 'One Request update profile coming from the chef.',
            'url' => '/admin/shef-change-request'
        ];
    }
}