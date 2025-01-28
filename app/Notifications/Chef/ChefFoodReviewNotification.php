<?php

namespace App\Notifications\Chef;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChefFoodReviewNotification extends Notification
{
    use Queueable;

    public $reviewDetails;
    public $message;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($reviewDetails, $message)
    {
        $this->reviewDetails = $reviewDetails;
        $this->message = $message;
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
            'message' => (($this->reviewDetails['user']->firstName . ' ' . $this->reviewDetails['user']->lastName) . $this->message . date('d M Y', strtotime(Carbon::now())) . '.'),
            'url' => '/chef/food-reviews'

        ];
    }
}
