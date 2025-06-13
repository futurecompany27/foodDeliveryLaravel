<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Chef extends Authenticatable implements JWTSubject
{
    use HasFactory, SoftDeletes;
    use Notifiable;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'is_hfc_paid',
        'is_rrc_paid',
        'email',
        'password',
        'status',
        'are_you_a',
        'is_personal_details_completed',
        'is_special_benefit_document_completed',
        'is_document_details_completed',
        'is_fhc_document_completed',
        'is_rrc_certificate_document_completed',
        'is_bank_detail_completed',
        'is_social_detail_completed',
        'is_kitchen_detail_completed',
        'is_tax_document_completed',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'kitchen_types' => 'array',
        'chefAvailibilityWeek' => 'array',
        'blacklistedUser' => 'array'
    ];


    public function chefDocuments()
    {
        return $this->hasMany(ChefDocument::class, 'chef_id', 'id');
    }

    public function foodItems()
    {
        return $this->hasMany(FoodItem::class, 'chef_id', 'id');
    }

    public function alternativeContacts()
    {
        return $this->hasMany(ChefAlternativeContact::class, 'chef_id', 'id');
    }

    public function UserFoodReview()
    {
        return $this->hasMany(UserFoodReview::class, 'chef_id', 'id');
    }

    public function UserChefReview()
    {
        return $this->hasMany(UserChefReview::class, 'chef_id', 'id');
    }

    public function foodLicense()
    {
        return $this->hasOne(FoodLicense::class);
    }
    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    public function story()
    {
        return $this->hasOne(ChefStory::class, 'chef_id', 'id');
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
            'expires_in' => auth('chef')->factory()->getTTL() * 1440,
            'success' => true,
            'message' => 'Token generated successfully!'
        ];
    }


}
