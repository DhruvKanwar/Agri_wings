<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FarmerProfile extends Model
{
    use HasFactory;
    protected $fillable = [
        'farmer_id',
        'gender',
        'income',
        'education_level',
        'date_of_birth',
        'wedding_anniversary',
        'attitude',
        'lifestyle',
        'professional_info',
        'influence',
        'hobbies',
        'favourite_activities',
        'intrests',
        'mobile_phone_used',
        'social_media_platform',
        'tech_proficiency',
        'prferred_communication',
        'email_id',
        'ratings',
        'suggestion_for_improvement',
    ];
}
