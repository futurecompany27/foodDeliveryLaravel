<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Authenticatable implements JWTSubject
{
    use HasFactory, SoftDeletes;
    use Notifiable;

    protected $fillable = [
        'firstName',
        'lastName',
        'email',
        'mobileNo',
        'availability',
        'is_personal_details_completed',
        'is_driving_license_document_completed',
        'is_address_proof_document_completed',
        'is_tax_document_completed',
        'is_bank_document_detail',

    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function driverScheduleCall()
    {
        return $this->hasMany(DriverScheduleCall::class, 'driver_id', 'id');
    }
    public function otps()
    {
        return $this->hasMany(OTP::class);
    }
    public function driverContact()
    {
        return $this->hasMany(DriverContact::class);
    }

    public function driverSuggestion()
    {
        return $this->hasMany(DriverSuggestion::class);
    }
    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    public function driverProfileReviewByAdmin()
    {
        return $this->belongsTo(DriverProfileReviewByAdmin::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    /**
     * Return a key value array, containing any custom claims to be added to the JWT
     *
     * @return
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public static function createToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('driver')->factory()->getTTL() * 1440, //24 Hours in minutes
            'success' => true,
            'message' => 'Token generated successfully!'
        ];
    }

    public function scopeNearby($query, $latitude, $longitude, $radius = 10)
    {
        $haversine = "(6371 * acos(cos(radians($latitude)) * cos(radians(latitude)) * cos(radians(longitude) - radians($longitude)) + sin(radians($latitude)) * sin(radians(latitude))))";
        return $query->select('*')->selectRaw("{$haversine} AS distance")->having('distance', '<', $radius);
    }


}
