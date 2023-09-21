<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class chef extends Model
{
    use HasFactory;
    use Notifiable;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
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
}