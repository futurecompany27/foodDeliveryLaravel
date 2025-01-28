<?php

namespace App\Notifications\Driver;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\Logger;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;


class SuborderCronJobDriverNotify extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */

    protected $suborder, $driver;

    public function __construct($suborder, $driver)
    {
        $this->driver = $driver;
        $this->suborder = $suborder;
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

    public function toArray($notifiable)
    {
        return [
            'id' => $this->driver['id'],
            'message' => ('You have a new delivery scheduled for'. $this->suborder->orders->delivery_date .'Please check your app for more details. Thank you!'),
            Log::info('working'),
            'url' => '/delivery/orders'
        ];
    }

    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //         ->line('The introduction to the notification.')
    //         ->action('Notification Action', url('/'))
    //         ->line('Thank you for using our application!');
    // }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    // public function toArray(object $notifiable): array
    // {
    //     return [
    //         //
    //     ];
    // }
}
