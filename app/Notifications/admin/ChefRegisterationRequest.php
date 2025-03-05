<?php

namespace App\Notifications\admin;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChefRegisterationRequest extends Notification
{
    use Queueable;
    public $chefDetail;
    /**
     * Create a new notification instance.
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
    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->chefDetail->id,
            'message' => (($this->chefDetail->firstName . ' ' . $this->chefDetail->lastName) . ' has requested to become chef on ' . date('d M Y', strtotime($this->chefDetail->created_at)) . '.'),
            'url' => '/admin/chef-request'
        ];
    }
}