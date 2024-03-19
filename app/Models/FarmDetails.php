<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmDetails extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'farmer_id', 
        'field_area', 
        'sub_district',
        'address',
         'acerage',
          'pin_code',
        'location_coordinates',
           'village',
            'district', 
            'state', 
            'saved_by_id', 'saved_by_name', 'updated_by_id', 'updated_by_name', 'created_at', 'updated_at'
    ];

    public function FarmerInfo()
    {
        return $this->hasMany('App\Models\FarmerDetails', 'id', 'farmer_id');
    }
}


    // Retrieve All Records (Including Soft-Deleted):
    // $allRecords = FarmDetails::withTrashed()->get();

    // Retrieve Only Soft-Deleted Records:
    // $softDeletedRecords = FarmDetails::onlyTrashed()->get();

    // Retrieve Specific Record with Soft-Deleted:
    // $recordWithTrashed = FarmDetails::withTrashed()->find($id);

    // Retrieve Records Based on Conditions with Soft-Deleted:
// $recordsWithTrashed = FarmDetails::withTrashed()
//     ->where('field_area', 'like', '%example%')
//     ->get();