<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentItemField extends Model
{
    use HasFactory;
    protected $table = "document_item_fields";
    protected $fillable = ['id', 'document_item_list_id', 'field_name', 'type', 'mandatory', 'allows_as_kitchen_name'];
    // public function document_item()
    // {
    //     return $this->belongsTo(DocumentItemList::class);
    // }

    public function documentItemList()
    {
        return $this->belongsTo(DocumentItemList::class, 'document_item_list_id', 'id');
    }
}
