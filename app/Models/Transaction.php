<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
class Transaction extends Model
{
    use HasFactory;

    const TYPE_ORDER = 1;
    const TYPE_HANDLER_CERTIFICATE = 2;
    const TYPE_LICENSE_CERTIFICATE = 3;

    protected $fillable = [
        'transaction_type', 
        'user_type', 
        'user_id', 
        'remark',
        'status',
        'amount',
        'payment_log',
        'payment_id',
        'txn_no'
    ];

    public static $types = [
        self::TYPE_ORDER => 'Customer Order',
        self::TYPE_HANDLER_CERTIFICATE => 'Food Handler Certificate',
        self::TYPE_LICENSE_CERTIFICATE => 'Restaurant & Retail Licence Certificate'
    ];


    public function chef(){
        return $this->belongsTo(Chef::class, 'user_id', 'id');
    }
    public function driver(){
        return $this->belongsTo(Driver::class, 'user_id', 'id');
    }

    public static function generateTransactionNo()
    {
        $transactionNo = '';
        do{
            $transactionNo = 'TR'.substr(str_shuffle(MD5(microtime())), 0, 10);
        }
        while(self::where('txn_no', $transactionNo)->exists());

        return $transactionNo;
    }

}
