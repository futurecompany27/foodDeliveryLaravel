<?php

namespace App\Console\Commands;

use App\Mail\Chef\ChefIsTaxableMail;
use App\Models\Admin;
use App\Models\Chef;
use App\Models\SubOrders;
use App\Notifications\admin\ChefIsTaxable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NonTaxableChefsCheck extends Command
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chefs:check-non-taxable';
    // protected $signature = 'app:non-taxable-chefs-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check non-taxable chefs who exceed the earning limit and notify them.';
    // protected $description = 'Command description';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        // Define the earning limit
        $earningLimit = 300;
        // Get current month
        $currentMonth = now()->startOfMonth();

        Log::info('Current Month', [$currentMonth]);

        // Fetch non-taxable chefs
        $chefs = Chef::where('is_taxable', 'no')->get();

        foreach ($chefs as $chef) {
            // Calculate total earning for the current month
            $totalEarnings = SubOrders::where('chef_id', $chef->id)
                ->where('created_at', '>=', $currentMonth)
                ->sum('chef_earning');

            // Check if the earnings exceed the limit
            if ($totalEarnings > $earningLimit) {
                // Send email to chef
                $this->mailToChef($chef, $totalEarnings);

                // Notify admin
                $this->notifyAdmin($chef, $totalEarnings);

                // Make chef account inactive
                $chef->update(['status' => '0']);
                Log::info("Chef {$chef->firstName} has been deactivated for exceeding the earning limit.");
            }
        }
        return 0;
    }


    protected function mailToChef($chef, $totalEarnings)
    {
        Mail::to(trim($chef->email))->send(new ChefIsTaxableMail($chef, $totalEarnings));
    }

    protected function notifyAdmin($chef, $totalEarnings)
    {
        $admins = Admin::all();
        foreach ($admins as $admin) {
            $admin->notify(new ChefIsTaxable($chef));
        }
    }
}
