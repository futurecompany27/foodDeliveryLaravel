<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HomeshefChefStatusChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $chefDetail;
    /**
     * Create a new message instance.
     */
    public function __construct($chefDetail)
    {
        $this->chefDetail = $chefDetail;
    }

    public function build()
    {
        return $this->subject('Homeplate Change Mail Status')
            ->view('chefChangeStatusMail', ['id' => $this->chefDetail->id, 'firstName' => ucfirst($this->chefDetail->firstName), "lastName" => ucfirst($this->chefDetail->lastName), 'status' => $this->chefDetail->status]);
    }
}