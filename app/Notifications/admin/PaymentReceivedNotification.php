<?php

namespace App\Notifications\admin;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification
{
    use Queueable;

    public $chefDetails;
    /**
     * Create a new notification instance.
     */
    public function __construct($chefDetails)
    {
        $this->chefDetails = $chefDetails;
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

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->chefDetails->id,
            'message' => (ucfirst($this->chefDetails->firstName) . ' ' . ucfirst($this->chefDetails->lastName)) . ' is Paid the FHC on ' . date('d M Y', strtotime(Carbon::now() . '.')),
            'url' => '/admin/chef-profile'
        ];
    }
}
