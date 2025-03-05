<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserContact extends Model
{
    use HasFactory;
    protected $table = 'user_contacts';
    protected $fillable = ['are_you_a', 'firstName','lastName', 'email', 'subject', 'message', 'status'];
}
