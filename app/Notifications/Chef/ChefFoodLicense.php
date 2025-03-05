<?php

namespace App\Notifications\Chef;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChefFoodLicense extends Notification
{
    use Queueable;
    public $foodLicense;

    /**
     * Create a new notification instance.
     */
    public function __construct($foodLicense)
    {
        $this->foodLicense = $foodLicense;
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
            'id' => $this->foodLicense['id'],
            'firstName' => $this->foodLicense->chef['firstName'],
            'lastName' => $this->foodLicense->chef['lastName'],
            'message' => (($this->foodLicense->chef['firstName'] . ' ' . $this->foodLicense->chef['lastName']) . 'Submited Food-license Form. '. date('d M Y', strtotime($this->foodLicense->created_at)) . '.'),
            'url' => '/admin/food-certificate'
        ];
    }
}
