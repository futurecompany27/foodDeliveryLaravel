<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentItemList extends Model
{
    use HasFactory, SoftDeletes;
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
        return $this->hasMany(DocumentItemField::class, 'document_item_list_id', 'id');
    }

    public static function booted()
    {
        static::deleting(function ($documentItem) {
            DocumentItemField::where('document_item_list_id', $documentItem->id)
                ->get()
                ->each(function ($field) {
                    $field->delete();
                });
        });
    }
}
