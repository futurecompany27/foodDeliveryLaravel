<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class subOrderDeclineMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $mail;
    /**
     * Create a new message instance.
     */
    public function __construct($mail)
    {
        $this->mail = $mail;
    }

    public function build()
    {
        return $this->view('orderHasBeenDeclined', ['subject' => "Chef declined order", 'userName' => $this->mail['userName'], 'chefName' => $this->mail['chefName'], 'order_id' => $this->mail['order_id']]);
    }
}
