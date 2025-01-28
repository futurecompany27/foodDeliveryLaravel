<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuborderReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    protected $suborder;

    public function __construct($suborder)
    {
        $this->suborder = $suborder;
    }

    /**
     * Get the message envelope.
     */
    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'Suborder Reminder Mail',
    //     );
    // }

    /**
     * Get the message content definition.
     */

    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'suborder_reminder_mail',
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
        $subject = "New Order {$this->suborder->sub_order_id} Requires Your Acceptance";
        return $this->subject($subject)
            ->view('suborder_reminder_mail')->with([
                'subject' => $subject,
                'suborder' => $this->suborder,
            ]);
    }
}
