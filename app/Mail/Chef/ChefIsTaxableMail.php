<?php

namespace App\Mail\Chef;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChefIsTaxableMail extends Mailable
{
    use Queueable, SerializesModels;

    public $chefDetail;
    public $totalEarnings;

    /**
     * Create a new message instance.
     */
    public function __construct($chefDetail, $totalEarnings)
    {
        $this->chefDetail = $chefDetail;
        $this->totalEarnings = $totalEarnings;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Chef Is Taxable ',
        );
    }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.chef-is-taxable',
    //     );
    // }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Chef Is Taxable')
            ->view('chef-is-taxable', ['id' => $this->chefDetail->id, 'firstName' => ucfirst($this->chefDetail->firstName), "lastName" => ucfirst($this->chefDetail->lastName), "totalEarnings" => $this->totalEarnings]);
    }
}
