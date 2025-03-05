<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedMailToUser extends Mailable
{
    use Queueable, SerializesModels;

    protected $orderDetails;

    /**
     * Create a new message instance.
     */
    public function __construct($orderDetails)
    {
        $this->orderDetails = $orderDetails;
    }

    public function build()
    {
        return $this
        ->subject('Your Order is Confirmed!')
        ->view('orderHasBeenPlaced', ['subject' => "Your Order is Confirmed!",
        'userName' => $this->orderDetails['userName'],
        'order_id' => $this->orderDetails['order_id'],
        'grand_total' => $this->orderDetails['grand_total'],
        'dishNames' => $this->orderDetails['dishNames'],
        'total_order_item' => $this->orderDetails['total_order_item'],
        'created_at' => $this->orderDetails['created_at']
        ]);
    }
}
