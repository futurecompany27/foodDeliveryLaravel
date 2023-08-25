<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HomeshefChefEmailVerification extends Mailable
{
    use Queueable, SerializesModels;

    protected $chefDetail;

    /**
     * Create a new message instance.
     */
    public function __construct($chefDetail)
    {
        $this->chefDetail = $chefDetail;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('ChefEmailVerification', ['id' => $this->chefDetail->id, 'full_name' => (ucfirst($this->chefDetail->first_name) . " " . ucfirst($this->chefDetail->last_name))]);
    }
}