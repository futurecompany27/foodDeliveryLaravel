<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChefProfileReviewByAdmin extends Model
{
    use HasFactory;
    
    protected $table = 'chef_profile_review_by_admin';
    protected $fillable = [
        'chef_id',
        'remark',
        'status'
    ];
}
