<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HomeshefDriverEmailVerifiedSuccessfully extends Mailable
{
    use Queueable, SerializesModels;
    public $driverDetails;

    /**
     * Create a new message instance.
     */
    public function __construct($driverDetails)
    {
        $this->driverDetails = $driverDetails;
    }

    public function build()
    {
        return $this->subject('Homeplate Driver Email Verified Successfully')
            ->view('driverEmailVerifiedSuccessfully', ['id' => $this->driverDetails->id, 'firstName' => ucfirst($this->driverDetails->firstName), 'lastName' => ucfirst($this->driverDetails->lastName)]);
    }
}
