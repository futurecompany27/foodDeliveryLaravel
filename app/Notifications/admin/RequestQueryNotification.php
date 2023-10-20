<?php

namespace App\Notifications\admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestQueryNotification extends Notification
{
    use Queueable;

    public $request_query;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($request_query)
    {
        $this->request_query = $request_query;
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
            'type' => $this->request_query->type,
            'id' => $this->request_query->chef_id,
            'request_for' => $this->request_query->request_for,
            'message' => 'One Request update profile coming from the chef.',
            'url' => '/admin/shef-contact-us'
        ];
    }
}