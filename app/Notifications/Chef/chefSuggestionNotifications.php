<?php

namespace App\Notifications\Chef;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class chefSuggestionNotifications extends Notification
{
    use Queueable;

    protected $chefDetail;


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
    public function toArray($notifiable)
    {
        $today_date = Carbon::now()->format('d-M-Y h:m:i');
        return [
            'id' => $this->chefDetail['id'],
            'firstName' => $this->chefDetail['firstName'],
            'lastName' => $this->chefDetail['lastName'],
            'message' => ($this->chefDetail['firstName'] .' ' .$this->chefDetail['lastName'].' has given suggestion.'),
            'url' => '/admin/chef_suggestions'
        ];
    }
}
