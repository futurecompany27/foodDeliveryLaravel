<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShefSubType extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'shef_subtypes';
    protected $fillable = [
        'name',
        'type_id',
        'status'
    ];


    public function shef_type()
    {
        return $this->belongsTo(ShefType::class,'type_id','id');
    }
}
