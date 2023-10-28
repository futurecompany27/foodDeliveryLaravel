<?php

namespace App\Notifications\Chef;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class newOrderPlacedForChef extends Notification
{
    use Queueable;
    public $subOrderDetail;
    /**
     * Create a new notification instance.
     */
    public function __construct($subOrderDetail)
    {
        $this->subOrderDetail = $subOrderDetail;
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
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->subOrderDetail['sub_order_id'],
            'message' => ($this->subOrderDetail['userName'] . ' has placed a new order at ' . date('d M Y', strtotime(Carbon::now())) . '.'),
            'url' => '/shef/order'
        ];
    }
}
