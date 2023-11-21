<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HomeshefUserEmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;
    protected $userDetail;

    /**
     * Create a new message instance.
     */
    public function __construct($userDetail)
    {
        $this->userDetail = $userDetail;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('UserEmailVerification', ['id' => $this->userDetail->id, 'firstName' => $this->userDetail->firstName,'lastName'=> $this->userDetail->lastName]);
    }
}