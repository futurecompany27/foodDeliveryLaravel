<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PendingOrderRemnder extends Mailable
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
        return $this->view('orderReminder', ['subject' => "Chef declined order", 'chefName' => $this->mail['chefName'], 'timeElapsedInMinutes' => $this->mail['remaningTime'], 'slot' => $this->mail['slot']]);
    }
}
