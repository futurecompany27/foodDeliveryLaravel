<?php

namespace App\Notifications\Chef;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewChefReviewNotification extends Notification
{
    use Queueable;

    public $reviewDetails;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($reviewDetails)
    {
        $this->reviewDetails = $reviewDetails;
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
            'id' => $this->reviewDetails['user']->id,
            'message' => ($this->reviewDetails['user']->firstName . ' ' . $this->reviewDetails['user']->lastName) . ' send a review to you on ' . date('d M Y', strtotime($this->reviewDetails['date'])),
            'url' => '/chef/my-reviews'

        ];
    }
}