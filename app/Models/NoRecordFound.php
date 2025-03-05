<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoRecordFound extends Model
{
    use HasFactory;
    protected $table = 'no_record_found';
    protected $fillable = [
        'postal_code',
        'firstName',
        'lastName',
        'email'
    ];
}