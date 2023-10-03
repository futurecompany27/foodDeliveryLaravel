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
        return $this->view('passwordResetLink', [
            'id' => $this->userDetails['id'],
            'fullname' => $this->userDetails['full_name'],
            'user_type' => $this->userDetails['user_type'],
            'token' => $this->userDetails['token'],
        ]);
    }
}