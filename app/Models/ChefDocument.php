<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChefDocument extends Model
{
    use HasFactory;

    protected $fillable = ['chef_id', 'document_field_id', 'field_value'];
}
