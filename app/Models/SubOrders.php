<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class SubOrders extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'sub_order_id',
        'chef_id',
        'chef_commission',
        'chef_commission_amount',
        'chef_commission_taxes',
        'chef_sale_taxes',
        'driver_commission',
        'driver_commission_amount',
        'driver_commission_taxes',
        'sub_order_tax_detail',
        'track_id',
        'item_total',
        'amount',
        'tip',
        'tip_type',
        'tip_amount',
        'status',
        'delivery_id',
        'delivery_proof_img',
        'pickup_token',
        'customer_delivery_token',
        'reason'
    ];
    protected $hidden = [
        'pickup_token',
        'customer_delivery_token',
    ];

    public function Orders()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function OrderItems()
    {
        return $this->hasMany(OrderItems::class, 'sub_order_id', 'sub_order_id');
    }

    public function OrderTrack()
    {
        return $this->hasMany(OrderTrackDetails::class, 'track_id', 'track_id');
    }

    public function chefs()
    {
        return $this->belongsTo(Chef::class, 'chef_id', 'id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id', 'id');
    }

    public function deliveryProofImg(): Attribute
    {
        return new Attribute(
            get: fn ($value) => asset($value),

        );
    }

    public static function generateDeliveryToken()
    {
        $deliveryToken = '';
        do {
            $deliveryToken = 'tkn' . substr(str_shuffle(MD5(microtime())), 0, 10);
        } while (self::where('txn_no', $deliveryToken)->exists());

        return $deliveryToken;
    }

    public static function generateUniquerPickupToken($tokenLength = 4)
    {
        // Define the characters (only digits here)
        $characters = '0123456789';
        $pickupToken = '';
        // Generate a token and ensure it is unique
        do {
            // Generate the token
            $pickupToken = '';
            for ($i = 0; $i < $tokenLength; $i++) {
                $pickupToken .= $characters[random_int(0, strlen($characters) - 1)];
            }
            // Check if token exists in the database
            $existdeliveryToken = self::where('pickup_token', $pickupToken)->exists();
        } while ($existdeliveryToken); // Regenerate if token exists

        return $pickupToken;
    }

    public static function generateUniqueCustomerDeliveryToken($tokenLength = 4)
    {
        // Define the characters (only digits here)
        $characters = '0123456789';
        $customerToken = '';

        // Generate a token and ensure it is unique
        do {
            // Generate the token
            $customerToken = '';
            for ($i = 0; $i < $tokenLength; $i++) {
                $customerToken .= $characters[random_int(0, strlen($characters) - 1)];
            }

            // Check if token exists in the database
            $existCustomerToken = self::where('customer_delivery_token', $customerToken)->exists();
        } while ($existCustomerToken); // Regenerate if token exists

        return $customerToken;
    }
}
