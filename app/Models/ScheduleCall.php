<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleCall extends Model
{
    use HasFactory;
    protected $fillable = ['chef_id', 'date', 'slot', 'status'];
}
