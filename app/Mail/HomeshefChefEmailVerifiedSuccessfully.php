<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HomeshefChefEmailVerifiedSuccessfully extends Mailable
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

    public function build()
    {
        return $this->view('chefEmailVerifiedSuccessfully')->with(['id' => $this->chefDetails->id, 'full_name' => ucfirst($this->chefDetails->first_name) . " " . ucfirst($this->chefDetails->last_name)]);
    }
}