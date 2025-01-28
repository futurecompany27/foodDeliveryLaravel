<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChefSuggestion extends Model
{
    use HasFactory;
    protected $table = "chef_suggestions";
    protected $fillable = ['id', 'related_to', 'message', 'sample_pic', 'chef_id'];

    public function chef()
    {
        return $this->belongsTo(Chef::class, 'chef_id', 'id');
    }
}
