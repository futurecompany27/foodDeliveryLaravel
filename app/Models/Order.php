<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_number',
        'tax_types',
        'order_total',
        'order_tax',
        'order_date',
        'shipping',
        'shipping_tax',
        'discount_amount',
        'discount_tax',
        'grand_total',
        'user_id',
        'shipping_address',
        'city',
        'state',
        'landmark',
        'postal_code',
        'latitude',
        'longitude',
        'payment_mode',
        'delivery_date',
        'from_time',
        'to_time',
        'food_instruction',
        'delivery_option',
        'option_desc',
        'delivery_instructions',
        'payment_status',
        'transacton_id',
        'total_order_item',
        'tip_total',
        'user_mobile_no',
        'username',
        'token'
    ];

    protected $casts = [
        'tax_types' => 'array',
    ];

    public function subOrders()
    {
        return $this->hasMany(SubOrders::class, 'order_id', 'order_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id','id');
    }
    public function chef(){
        return $this->belongsTo(Chef::class, 'user_id','id');
    }

    /**
     * Generate comprehensive order summary data for frontend
     * 
     * @return array
     */
    public function generateOrderSummaryData()
    {
        $subOrders = $this->subOrders()->with(['chefs', 'driver'])->get();
        $adminSettings = \App\Models\Adminsetting::first();
        
        // Initialize totals
        $totalChefCommission = 0;
        $totalChefCommissionGST = 0;
        $totalChefCommissionQST = 0;
        $totalDriverCommission = 0;
        $totalDriverCommissionGST = 0;
        $totalDriverCommissionQST = 0;
        $totalChefServiceCharges = 0;
        $totalChefServiceChargesGST = 0;
        $totalChefServiceChargesQST = 0;
        $totalDriverServiceCharges = 0;
        $totalDriverServiceChargesGST = 0;
        $totalDriverServiceChargesQST = 0;
        $totalChefEarning = 0;
        $totalDriverEarning = 0;
        
        $subOrderDetails = [];
        
        foreach ($subOrders as $subOrder) {
            // Calculate chef commission taxes
            $chefCommissionTaxes = is_string($subOrder->chef_commission_taxes) 
                ? json_decode($subOrder->chef_commission_taxes, true) 
                : $subOrder->chef_commission_taxes;
            
            $chefCommissionGST = 0;
            $chefCommissionQST = 0;
            
            if (is_array($chefCommissionTaxes)) {
                foreach ($chefCommissionTaxes as $tax) {
                    if (isset($tax['GST'])) {
                        $chefCommissionGST += $tax['Amount'] ?? 0;
                    }
                    if (isset($tax['QST'])) {
                        $chefCommissionQST += $tax['Amount'] ?? 0;
                    }
                }
            }
            
            // Calculate driver commission taxes
            $driverCommissionTaxes = is_string($subOrder->driver_commission_taxes) 
                ? json_decode($subOrder->driver_commission_taxes, true) 
                : $subOrder->driver_commission_taxes;
            
            $driverCommissionGST = 0;
            $driverCommissionQST = 0;
            
            if (is_array($driverCommissionTaxes)) {
                foreach ($driverCommissionTaxes as $tax) {
                    if (isset($tax['GST'])) {
                        $driverCommissionGST += $tax['Amount'] ?? 0;
                    }
                    if (isset($tax['QST'])) {
                        $driverCommissionQST += $tax['Amount'] ?? 0;
                    }
                }
            }
            
            // Calculate service charges
            $chefServiceCharges = $adminSettings ? $adminSettings->chef_service_charges : 0;
            $driverServiceCharges = $adminSettings ? $adminSettings->driver_service_charges : 0;
            
            // Calculate service charge taxes using actual tax rates from the system
            $chefServiceChargesGST = round($chefServiceCharges * 0.05, 2); // 5% GST
            $chefServiceChargesQST = round($chefServiceCharges * 0.09975, 2); // 9.975% QST
            $driverServiceChargesGST = round($driverServiceCharges * 0.05, 2);
            $driverServiceChargesQST = round($driverServiceCharges * 0.09975, 2);
            
            // Calculate total commission from chef
            $totalCommissionFromChef = $subOrder->chef_commission_amount + $chefCommissionGST + $chefCommissionQST;
            
            // Calculate total commission from driver
            $totalCommissionFromDriver = $subOrder->driver_commission_amount + $driverCommissionGST + $driverCommissionQST;
            
            // Calculate total service charges from chef
            $totalServiceChargesFromChef = $chefServiceCharges + $chefServiceChargesGST + $chefServiceChargesQST;
            
            // Calculate total service charges from driver
            $totalServiceChargesFromDriver = $driverServiceCharges + $driverServiceChargesGST + $driverServiceChargesQST;
            
            // Calculate earnings
            $chefEarning = $subOrder->amount - $totalCommissionFromChef - $totalServiceChargesFromChef;
            $driverEarning = $totalCommissionFromDriver + $totalServiceChargesFromDriver;
            
            // Accumulate totals
            $totalChefCommission += $subOrder->chef_commission_amount;
            $totalChefCommissionGST += $chefCommissionGST;
            $totalChefCommissionQST += $chefCommissionQST;
            $totalDriverCommission += $subOrder->driver_commission_amount;
            $totalDriverCommissionGST += $driverCommissionGST;
            $totalDriverCommissionQST += $driverCommissionQST;
            $totalChefServiceCharges += $chefServiceCharges;
            $totalChefServiceChargesGST += $chefServiceChargesGST;
            $totalChefServiceChargesQST += $chefServiceChargesQST;
            $totalDriverServiceCharges += $driverServiceCharges;
            $totalDriverServiceChargesGST += $driverServiceChargesGST;
            $totalDriverServiceChargesQST += $driverServiceChargesQST;
            $totalChefEarning += $chefEarning;
            $totalDriverEarning += $driverEarning;
            
            // Add sub-order details
            $subOrderDetails[] = [
                'sub_order_no' => $subOrder->sub_order_id,
                'chef_name' => $subOrder->chefs ? $subOrder->chefs->firstName . ' ' . $subOrder->chefs->lastName : 'N/A',
                'driver_name' => $subOrder->driver ? $subOrder->driver->firstName . ' ' . $subOrder->driver->lastName : 'N/A',
                'amount' => $subOrder->amount,
                'chef_commission' => $subOrder->chef_commission_amount,
                'chef_commission_gst' => $chefCommissionGST,
                'chef_commission_qst' => $chefCommissionQST,
                'total_commission_from_chef' => $totalCommissionFromChef,
                'driver_commission' => $subOrder->driver_commission_amount,
                'driver_commission_gst' => $driverCommissionGST,
                'driver_commission_qst' => $driverCommissionQST,
                'total_commission_from_driver' => $totalCommissionFromDriver,
                'chef_service_charges' => $chefServiceCharges,
                'chef_service_charges_gst' => $chefServiceChargesGST,
                'chef_service_charges_qst' => $chefServiceChargesQST,
                'total_service_charges_from_chef' => $totalServiceChargesFromChef,
                'driver_service_charges' => $driverServiceCharges,
                'driver_service_charges_gst' => $driverServiceChargesGST,
                'driver_service_charges_qst' => $driverServiceChargesQST,
                'total_service_charges_from_driver' => $totalServiceChargesFromDriver,
                'chef_earning' => $chefEarning,
                'driver_earning' => $driverEarning,
            ];
        }
        
        // Calculate admin earning (total order amount - chef earnings - driver earnings)
        $totalAdminEarning = $this->grand_total - $totalChefEarning - $totalDriverEarning;
        
        return [
            'order_no' => $this->order_id,
            'date' => $this->order_date,
            'order_amount' => $this->grand_total,
            'sub_orders' => $subOrderDetails,
            'summary' => [
                'commission_for_all_chefs' => $totalChefCommission,
                'tax_amount_for_all_chef_gst' => $totalChefCommissionGST,
                'tax_amount_for_all_chef_qst' => $totalChefCommissionQST,
                'total_commission_from_chef' => $totalChefCommission + $totalChefCommissionGST + $totalChefCommissionQST,
                'commission_for_driver' => $totalDriverCommission,
                'tax_amount_for_driver_gst' => $totalDriverCommissionGST,
                'tax_amount_for_driver_qst' => $totalDriverCommissionQST,
                'total_commission_from_driver' => $totalDriverCommission + $totalDriverCommissionGST + $totalDriverCommissionQST,
                'service_charges_from_all_chef' => $totalChefServiceCharges,
                'service_charges_for_driver_gst' => $totalDriverServiceChargesGST,
                'service_charges_for_driver_qst' => $totalDriverServiceChargesQST,
                'total_service_charges_from_chef' => $totalChefServiceCharges + $totalChefServiceChargesGST + $totalChefServiceChargesQST,
                'service_charges_from_drivers' => $totalDriverServiceCharges,
                'service_charges_for_chef_tps' => $totalChefServiceChargesGST, // TPS is same as GST in Quebec
                'service_charges_for_chef_qst' => $totalChefServiceChargesQST,
                'total_service_charges_from_drivers' => $totalDriverServiceCharges + $totalDriverServiceChargesGST + $totalDriverServiceChargesQST,
                'total_chef_earning' => $totalChefEarning,
                'total_driver_earning' => $totalDriverEarning,
                'total_admin_earning' => $totalAdminEarning,
            ]
        ];
    }
}
