<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HomeshefDriverEmailVerrifiedSuccessfully extends Mailable
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
        return $this->view('driverEmailVerifiedSuccessfully', ['id' => $this->driverDetails->id, 'full_name' => ucfirst($this->driverDetails->first_name) . " " . ucfirst($this->driverDetails->last_name)]);
    }
}
