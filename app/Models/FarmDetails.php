<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FarmDetails extends Model
{
    use HasFactory;
    protected $fillable = [
        'farmer_id', 
        'field_area', 
        'sub_district',
        'address',
         'acerage',
          'pin_code',
           'village',
            'district', 
            'state', 
            'saved_by_id', 'saved_by_name', 'updated_by_id', 'updated_by_name', 'created_at', 'updated_at'
    ];
}
