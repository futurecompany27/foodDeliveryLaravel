<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    protected $txn, $chef;
    public function __construct($chef, $txn)
    {
        $this->txn = $txn;
        $this->chef = $chef;
    }

    /**
     * Get the message envelope.
     */
    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'Transaction Mail',
    //     );
    // }

    // /**
    //  * Get the message content definition.
    //  */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.name',
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
        return $this->subject($this->txn->transaction . ' Tansaction Mail')
            ->view('transaction_mail', [
                'id' => $this->txn->id,
                'subject'=> ($this->txn->remark),
                'transaction_type' => ($this->txn->transaction_type),
                'firstName' => ucfirst($this->chef->firstName),
                'lastName' => ucfirst($this->chef->lastName)
            ]);
    }
}
