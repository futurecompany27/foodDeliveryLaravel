<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceivedMailToChef extends Mailable
{
    use Queueable, SerializesModels;

    protected $chefDetails, $transaction;
    /**
     * Create a new message instance.
     */
    public function __construct($chefDetails, $transaction)
    {
        $this->chefDetails = $chefDetails;
        $this->transaction = $transaction;
    }


    /**
     * Get the message content definition.
     */


    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function build()
    {
        return $this->subject('Payment Received to Homeplate')
            // ->view('payment-received-hfc');
            ->view('payment-received-hfc', ['firstName' => ucfirst($this->chefDetails->firstName), 'lastName' => ucfirst($this->chefDetails->lastName), 'transaction_id' => $this->transaction->id, 'transaction_amount' => $this->transaction->amount ]);

    }
}
