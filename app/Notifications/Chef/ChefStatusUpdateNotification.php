<?php

namespace App\Notifications\Chef;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChefStatusUpdateNotification extends Notification
{
    use Queueable;

    protected $chefDetail;
    /**
     * Create a new notification instance.
     *
     * @return void
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
        $status_array = [
            0 => 'You account has been deactivated on ' . $today_date,
            1 => 'Congratulations! your account is activated now.',
            2 => 'Congratulation, your account is in review now'
        ];
        return [
            'id' => $this->chefDetail['id'],
            'full_name' => ($this->chefDetail['first_name'] . ' ' . $this->chefDetail['last_name']),
            'status' => $this->chefDetail['status'],
            'message' => $status_array[$this->chefDetail['status']]
        ];
    }
}