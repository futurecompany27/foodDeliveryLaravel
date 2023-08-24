<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HomeshefChefChangeEmailVerificationLink extends Mailable
{
    use Queueable, SerializesModels;

    public $chefDetails;
    /**
     * Create a new message instance.
     */
    public function __construct($chefDetails)
    {
        $this->chefDetails = $chefDetails;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('chefChangeEmailVerification')->with(['id' => $this->chefDetails->id, 'fullname' => (ucfirst($this->chefDetails->first_name) . " " . ucfirst($this->chefDetail->last_name))]);
    }
}