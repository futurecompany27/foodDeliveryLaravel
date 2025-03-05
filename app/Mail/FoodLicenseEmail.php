<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FoodLicenseEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $foodLicense;
    /**
     * Create a new message instance.
     */
    public function __construct($foodLicense)
    {
        $this->foodLicense = $foodLicense;
    }

    /**
     * Get the message envelope.
     */
    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'Food License Email',
    //     );
    // }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'food-license-email',
    //     );
    // }
    
    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    public function build()
    {
        return $this->subject('Thank You for Submitting Your Food Certificate')
            ->view('food-license-email', ['id' => $this->foodLicense->id, 'firstName' => ucfirst($this->foodLicense->chef->firstName), "lastName" => ucfirst($this->foodLicense->chef->lastName)]);
    }
}
