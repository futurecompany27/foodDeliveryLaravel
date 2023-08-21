<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\chefs\ChefController;


class Contact extends Model
{
    use HasFactory;
    protected $table = "contacts";
    protected $fillable = ['chef_id', 'subject', 'message', 'status'];

    public function chef()
    {
        return $this->belongsTo(chef::class, 'chef_id', 'id');
    }
}
