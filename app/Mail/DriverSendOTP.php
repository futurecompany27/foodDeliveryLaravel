<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DriverSendOTP extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $user;
    /**
     * Create a new message instance.
     */

    public function __construct($otp, $user)
    {
        $this->otp = $otp;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Request Driver OTP || ' . env('APP_NAME'),
        );
    }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         markdown: 'emails.password-reset-otp',
    //     );
    // }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
    public function build()
    {
        // return $this->view('driverOtp', ['id' => $this->driver->id, 'firstName' => ucfirst($this->driver->firstName), "lastName" => ucfirst($this->driver->lastName)]);
        return $this->view('SendOtpToEmail', ['otp' => $this->otp, 'firstName' => ucfirst($this->user->firstName), "lastName" => ucfirst($this->user->lastName)]);
    }
}
