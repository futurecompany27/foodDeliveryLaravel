<?php

namespace App\Notifications\Customer;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerSearchNotification extends Notification
{
    use Queueable;

    protected $search;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($search)
    {
        $this->search = $search;
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
            // 'id' => $this->search->id,
            'firstName' => $this->search->firstName,
            'lastName' => $this->search->lastName,
            'postal_code' => $this->search->postal_code,
            'message' => $this->search->email . ' search shef in ' . $this->search->postal_code . ' postal code.'
        ];
    }
}