<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pincode extends Model
{
    use HasFactory;
    protected $fillable = ['city_id', 'pincode', 'latitude', 'longitude', 'status'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public static function serviceExistsForPostalCode(string $postalCode): bool
    {
        $normalized = str_replace(' ', '', strtoupper($postalCode));
        if (strlen($normalized) < 3) {
            return false;
        }

        $fsa = substr($normalized, 0, 3);

        return static::where('status', 1)
            ->where(function ($query) use ($normalized, $fsa) {
                $query->where('pincode', $normalized)
                    ->orWhere('pincode', $fsa)
                    ->orWhere('pincode', 'like', $fsa . '%');
            })
            ->exists();
    }
}
