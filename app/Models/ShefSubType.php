<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShefSubType extends Model
{
    use HasFactory;
    protected $table = 'shef_subtypes';
    protected $fillable = [
        'name',
        'status'
    ];


    public function shef_type()
    {
        return $this->belongsTo(ShefType::class,'type_id','id');
    }
}
