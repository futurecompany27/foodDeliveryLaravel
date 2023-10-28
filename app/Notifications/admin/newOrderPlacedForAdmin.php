<?php

namespace App\Notifications\admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class newOrderPlacedForAdmin extends Notification
{
    use Queueable;
    public $orderDetail;
    /**
     * Create a new notification instance.
     */
    public function __construct($orderDetail)
    {
        $this->orderDetail = $orderDetail;
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
            'id' => $this->orderDetail['order_id'],
            'message' => ($this->orderDetail['userName'] . ' has placed a new order at ' . date('d M Y', strtotime(Carbon::now())) . '.'),
            'url' => '/admin/orders'
        ];
    }
}
