<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentItemList extends Model
{
    use HasFactory;

    protected $fillable = ['state_id', 'document_item_name', 'chef_type', 'reference_links', 'additional_links', 'detail_information', 'status'];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function documentitemfields()
    {
        return $this->hasMany(DocumentItemField::class);
    }
}