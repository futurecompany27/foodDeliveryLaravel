<?php

namespace App\Notifications\chef;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChefEmailNotification extends Notification
{
    use Queueable;

    public $mail;

    /**
     * Create a new notification instance.
     */
    public function __construct($mail)
    {
        $this->mail = $mail;
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
            'message' => (ucfirst($this->mail['chef_name']) . ' you have received mail from Homeplate team on ' . date('d M Y', strtotime(Carbon::now())) . '.'),
            // 'message' => ($this->{{ $chef_name}} . 'You have received mail from Homeplate team on '  . date('d M Y', strtotime(Carbon::now())) . '.'),
            'url' => '/chef/chef-notification'
        ];
    }
}
