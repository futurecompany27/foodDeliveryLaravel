<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HomeshefCustomerEmailVerifiedSuccessfully extends Mailable
{
    use Queueable, SerializesModels;

    public $userDetails;
    /**
     * Create a new message instance.
     */
    public function __construct($userDetails)
    {
        $this->$userDetails = $userDetails;
    }

    public function build()
    {
        return $this->view('userEmailVerifiedSuccessfully')->with(['id' => $this->userDetails->id, 'full_name' => ucfirst($this->userDetails->fullname)]);
    }
}