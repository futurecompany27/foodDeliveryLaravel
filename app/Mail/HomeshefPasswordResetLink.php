<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HomeshefPasswordResetLink extends Mailable
{
    use Queueable, SerializesModels;
    public $userDetails;
    /**
     * Create a new message instance.
     */
    public function __construct($userDetails)
    {
        $this->userDetails = $userDetails;
    }

    public function build()
    {
        return $this->subject('Homeplate Password Reset Link')
            ->view('passwordResetLink', [
                'id' => $this->userDetails['id'],
                'firstName' => $this->userDetails['firstName'],
                'lastName' => $this->userDetails['lastName'],
                'user_type' => $this->userDetails['user_type'],
                'token' => $this->userDetails['token'],
            ]);
    }
}