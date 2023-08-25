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
        return $this->view('chefChangeStatusMail', ['id' => $this->chefDetail->id, 'full_name' => (ucfirst($this->chefDetail->first_name) . " " . ucfirst($this->chefDetail->last_name)), 'status'=> $this->chefDetail->status]);
    }
}