<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\chefs\ChefController;
use Illuminate\Database\Eloquent\SoftDeletes;


class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "contacts";
    protected $fillable = ['chef_id', 'subject', 'message', 'status'];

    public function chef()
    {
        return $this->belongsTo(Chef::class, 'chef_id', 'id');
    }
}
