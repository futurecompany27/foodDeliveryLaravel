<?php

namespace App\Console\Commands;

use App\Mail\SuborderReminderMail;
use App\Models\SubOrders;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendSuborderReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'app:send-suborder-reminder';
    protected $signature = 'check:suborders';

    /**
     * The console command description.
     *
     * @var string
     */
    // protected $description = 'Command description';
    protected $description = 'Check suborders and notify if status is not set';

    /**
     * Execute the console command.
     */

    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        try {
            // Fetch suborders where status is 2 means pending
            $suborders = SubOrders::where('status', 2)->with('chefs')->get();

            foreach ($suborders as $suborder) {
                // Logic to send email and SMS notifications
                // $retries = $suborder->retries;
                Mail::to($suborder->chefs->email)->send(new SuborderReminderMail($suborder));

            }

            Log::info('CheckSuborderStatus command executed successfully.');
        } catch (\Exception $e) {
            Log::error('Error in CheckSuborderStatus command: ' . $e->getMessage());
        }
    }


}
