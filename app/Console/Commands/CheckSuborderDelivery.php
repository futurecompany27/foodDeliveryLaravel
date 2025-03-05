<?php

namespace App\Console\Commands;

use App\Mail\SuborderCronJobDriverMail;
use App\Models\SubOrders;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Driver;
use App\Notifications\Driver\SuborderCronJobDriverNotify;
use Illuminate\Support\Facades\Notification;

class CheckSuborderDelivery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-suborder-delivery';

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
            Log::info('Driver Cron Job Run');
            // $dayAfterTomorrow = Carbon::now()->addDays(2)->startOfDay();
            $dayAfterTomorrow = Carbon::now()->addDays(2)->format('d/m/Y');

            $suborders = SubOrders::with('chefs', 'Orders')->whereHas('orders', function ($query) use ($dayAfterTomorrow) {
                $query->where('delivery_date', $dayAfterTomorrow);
            })->get();

            foreach ($suborders as $suborder) {
                $this->notifyDrivers($suborder);
            }
            Log::info('CheckSuborderDelivery command executed successfully.');
        } catch (\Exception $e) {
            Log::error('Error in CheckSuborderDelivery command: ' . $e->getMessage());
        }
    }

    private function notifyDrivers($suborder)
    {
        // Get the chef's location
        $chefLat = $suborder->chefs->latitude;
        $chefLong = $suborder->chefs->longitude;
        // Find nearby drivers based on chef's location (this assumes you have a scope for nearby in Driver model scope radius = 10 means fetch driver comes under 10 km)
        $drivers = Driver::nearby($chefLat, $chefLong)->get();

        foreach ($drivers as $driver) {
            // Send notifications
            Mail::to($driver->email)->send(new SuborderCronJobDriverMail($suborder, $driver));
            Notification::send($driver, new SuborderCronJobDriverNotify($suborder, $driver));

            Log::info('Notification sent to Driver: ' . $driver->email);
        }
    }
}
