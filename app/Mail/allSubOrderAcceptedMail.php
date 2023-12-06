<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class allSubOrderAcceptedMail extends Mailable {
    use Queueable, SerializesModels;

    protected $mail;
    /**
     * Create a new message instance.
     */
    public function __construct($mail) {
        $this->mail = $mail;
    }

    public function build() {
        return $this->view('allOrderHasBeenAccepted', ['subject' => 'Exciting News: Your Orders (Order ID: '.$this->mail['order_id'].') are Underway!', 'userName' => $this->mail['userName'], 'chefName' => $this->mail['chefName'], 'order_id' => $this->mail['order_id']]);
    }
}
