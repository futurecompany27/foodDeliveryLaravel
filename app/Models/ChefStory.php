<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChefStory extends Model
{
    protected $table = 'chef_stories';
    protected $primaryKey = 'chef_id';
    public $incrementing = false;

    protected $fillable = [
        'chef_id',
        'experience',
        'file',
    ];

    public function chef()
    {
        return $this->belongsTo(Chef::class, 'chef_id');
    }
}
