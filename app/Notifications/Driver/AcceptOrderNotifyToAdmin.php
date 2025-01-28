<?php

namespace App\Notifications\Driver;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AcceptOrderNotifyToAdmin extends Notification
{
    use Queueable;

    public $driverDetail, $subOrder;
    /**
     * Create a new notification instance.
     */
    public function __construct($driverDetail, $subOrder)
    {
        $this->driverDetail = $driverDetail;
        $this->subOrder = $subOrder;
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
    public function toArray($notifiable)
    {
        return [
            'id' => $this->driverDetail->id,
            'firstName' => $this->driverDetail->firstName,
            'lastName' => $this->driverDetail->lastName,
            'email' => $this->driverDetail->email,
            'suborderNo' => $this->subOrder->sub_order_id,
            'message' => ($this->driverDetail->firstName . ' ' . $this->driverDetail->lastName . ' has accepted order ' . $this->subOrder->sub_order_id . ' on ' . Carbon::now()->format('d M Y') . '.'),
            'url' => '/admin/new-contact-us'
        ];
    }
}
