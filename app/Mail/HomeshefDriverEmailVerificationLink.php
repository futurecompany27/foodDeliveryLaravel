<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HomeshefDriverEmailVerificationLink extends Mailable
{
    use Queueable, SerializesModels;
    protected $driver;
    /**
     * Create a new message instance.
     */
    public function __construct($driver)
    {
        $this->driver = $driver;
    }

    public function build()
    {
        return $this->subject('Homeplate Driver Email Verification')
            ->view('DriverEmailVerification', ['id' => $this->driver->id, 'firstName' => ucfirst($this->driver->firstName), "lastName" => ucfirst($this->driver->lastName)]);
    }
}