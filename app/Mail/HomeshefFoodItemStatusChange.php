<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HomeshefFoodItemStatusChange extends Mailable
{
    use Queueable, SerializesModels;
    public $chef;
    /**
     * Create a new message instance.
     */
    public function __construct($chef)
    {
        $this->chef = $chef;
    }

    public function build()
    {

        return $this->subject('Homeplate Food Item Status Change')->view('foodItemStatusChangeMail')
            ->with([
                'id' => $this->chef['id'],
                'firstName' => $this->chef['firstName'],
                'lastName' => $this->chef['lastName'],
                'food_name' => $this->chef['food_name'],
            ]);
    }
}
