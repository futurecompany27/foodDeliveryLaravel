<?php

namespace App\Notifications\admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class requestForBlacklistUser extends Notification
{
    use Queueable;
    public $blacklistRequest;
    /**
     * Create a new notification instance.
     */
    public function __construct($blacklistRequest)
    {
        $this->blacklistRequest = $blacklistRequest;
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
            'id' => $this->blacklistRequest->chef_id,
            'message' => 'One Request update profile coming from the chef.',
            'url' => '/admin/block-request'
        ];
    }
}
