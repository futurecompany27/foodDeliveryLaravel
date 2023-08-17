<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentItemList extends Model
{
    use HasFactory;
    protected $table = "document_item_lists";
    protected $fillable = ['id', 'state_id', 'document_item_name', 'chef_type', 'reference_links', 'additional_links', 'detail_information', 'status'];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
    public function shef_type()
    {
        return $this->belongsTo(ShefType::class, 'chef_type','id');
    }

    public function documentItemFields()
    {
        return $this->hasMany(DocumentItemList::class, 'document_item_list_id', 'id');
    }
}
