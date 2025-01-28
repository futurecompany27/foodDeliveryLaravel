<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DriverProfileStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $driverDetail;
    public function __construct($driverDetail)
    {
        $this->driverDetail = $driverDetail;
    }

    /**
     * Get the message envelope.
     */

    public function build()
    {
        return $this->subject('Homeplate Change Your Profile Status')
            ->view('driver-profile-status-mail',
            ['id' => $this->driverDetail->id,
            'firstName' => ucfirst($this->driverDetail->firstName),
            'lastName' => ucfirst($this->driverDetail->lastName),
            'status' => $this->driverDetail->status]);
    }
}
