<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SuborderCronJobDriverMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $suborder;
    public $driver;

    /**
     * Create a new message instance.
     *
     * @param $suborder
     * @param $driver
     */

    public function __construct($suborder, $driver)
    {
        $this->suborder = $suborder;
        $this->driver = $driver;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: Delivery Scheduled on ' . $this->suborder->orders->delivery_date,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'suborder_cron_job_driver_notify',
        );
    }

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
        Log::info('Driver in Mail', [$this->driver]);
        // Log::info($this->suborder);

        return $this->view('suborder_cron_job_driver_notify')
            ->with([
                'suborder' => $this->suborder,
                'driver' => $this->driver,
            ]);
    }
}
