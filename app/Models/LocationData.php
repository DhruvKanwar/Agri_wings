<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationData extends Model
{
    use HasFactory;
    protected $fillable = [
        'state_name',
        'state_code',
        'district_name',
        'district_code',
        'subdistrict_name',
        'subdistrict_code',
        'vil_town_name',
        'vil_town_code',
        'created_at', 'updated_at',
    ];
}
