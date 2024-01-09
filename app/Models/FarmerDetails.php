<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FarmerDetails extends Model
{
    use HasFactory;
    protected $table = 'farmer_details';

    protected $fillable = [
        'farmer_code',
        'farmer_name',
        'farmer_mobile_no',
        'farmer_pincode',
        'farmer_district',
        'farmer_state', 
        'farmer_sub_district', 
        'farmer_village',
        'farmer_address',
        'saved_by_id',
        'saved_by_name',
        'updated_by_id',
        'updated_by_name',
        'status',
        'created_at', 
        'updated_at'
    ];

    public function FarmInfo()
    {
        return $this->hasMany('App\Models\FarmDetails', 'farmer_id', 'id');
    }

    public function FarmerProfileInfo()
    {
        return $this->hasMany('App\Models\FarmerProfile', 'farmer_id', 'id');
    }
}
