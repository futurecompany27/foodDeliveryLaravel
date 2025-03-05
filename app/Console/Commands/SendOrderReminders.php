<?php

namespace App\Console\Commands;

use App\Mail\PendingOrderRemnder;
use App\Models\SubOrders;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:send-order-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $oneHourAgo = Carbon::now()->subHour();
            Log::info($oneHourAgo);
            $subOrders = SubOrders::where('status', 'Pending')->where('created_at', '>=', $oneHourAgo)->with('chefs')->get();
            foreach ($subOrders as $value) {

                $createdAt = $value->created_at;
                $currentTime = Carbon::now();
                $timeElapsedInMinutes = $currentTime->diffInMinutes($createdAt);

                $mail = ['chefName' => ($value->chefs->firstName . ' ' . $value->chefs->lastName), 'subOrderID' => $value->sub_order_id];

                if ($timeElapsedInMinutes >= 5 && $timeElapsedInMinutes <= 15) {
                    $mail['slot'] = 1;
                    $mail['subject'] = 'First reminder to accept - Pending Order';
                } else if ($timeElapsedInMinutes >= 16 && $timeElapsedInMinutes <= 30) {
                    $mail['slot'] = 2;
                    $mail['subject'] = 'Second reminder to accept - Pending Order';
                } else if ($timeElapsedInMinutes >= 31 && $timeElapsedInMinutes <= 45) {
                    $mail['slot'] = 3;
                    $mail['subject'] = 'Third reminder to accept - Pending Order';
                } else if ($timeElapsedInMinutes >= 46 && $timeElapsedInMinutes <= 55) {
                    $mail['slot'] = 4;
                    $mail['subject'] = 'Final reminder to accept - Pending Order';
                }
                $mail['remaningTime'] = $timeElapsedInMinutes;

                if ($timeElapsedInMinutes >= 5) {
                    Mail::to(trim($value->chefs->email))->send(new PendingOrderRemnder($mail));
                }
            }
            $this->info('Reminders sent successfully.');
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }

    }
}
