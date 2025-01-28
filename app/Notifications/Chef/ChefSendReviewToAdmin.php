<?php

namespace App\Notifications\Chef;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChefSendReviewToAdmin extends Notification
{
    use Queueable;

    public $chef;
    /**
     * Create a new notification instance.
     */
    public function __construct($chef)
    {
        $this->chef = $chef;
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
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //                 ->line('The introduction to the notification.')
    //                 ->action('Notification Action', url('/'))
    //                 ->line('Thank you for using our application!');
    // }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->chef->id,
            'message' => ucfirst($this->chef->firstName) . ' ' . ucfirst($this->chef->lastName) . ' sent a profile review request on ' . date('d M Y', strtotime(Carbon::now())),
            'url' => '/admin/chef-profile',
        ];
    }
}
