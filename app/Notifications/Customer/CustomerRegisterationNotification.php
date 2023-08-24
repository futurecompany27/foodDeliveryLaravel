<?php

namespace App\Notifications\Customer;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerRegisterationNotification extends Notification
{
    use Queueable;
    public $userDetail;

    /**
     * Create a new notification instance.
     */
    public function __construct($userDetail)
    {
        $this->userDetail = $userDetail;
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
        return [
            'id' => $this->userDetail->id,
            'email' => $this->userDetail->email,
            'message' => $this->userDetail->fullname . ' register as a new user.',
            'url' => '/admin/profile'
        ];
    }
}
