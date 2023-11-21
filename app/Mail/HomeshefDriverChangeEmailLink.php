<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HomeshefDriverChangeEmailLink extends Mailable
{
    use Queueable, SerializesModels;

    public $driver;
    /**
     * Create a new message instance.
     */
    public function __construct($driver)
    {
        $this->driver = $driver;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('driverChangeEmailVerification', ['id' => $this->driver->id, 'firstName' => ucfirst($this->driver->firstName), "lastName" => ucfirst($this->driver->lastName)]);
    }
}