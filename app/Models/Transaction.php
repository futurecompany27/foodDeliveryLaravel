<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

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
