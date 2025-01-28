<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShefType extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'name',
        'status'
    ];

    public function subtype()
    {
        return $this->hasMany(ShefSubtype::class);
    }

    public static function booted()
    {
        static::deleting(function ($shef_type) {
            ShefSubType::where('type_id', $shef_type->id)->get()->each(function ($subtype) {
                $subtype->delete();
            });
        });
    }
}
