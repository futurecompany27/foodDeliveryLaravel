<?php

namespace App\Notifications\Chef;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReviewNotification extends Notification
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
            'id' => $this->reviewDetails['id'],
            'chef_id' => $this->reviewDetails['chef']->id,
            'message' => $this->reviewDetails['user']->fullname . ' send a review to the chef ' . ($this->reviewDetails['chef']->first_name . ' ' . $this->reviewDetails['chef']->last_name) . ' on ' . date('d M Y', strtotime($this->reviewDetails['date'])),
            'url' => '/admin/view/chefs-review/'

        ];
    }
}