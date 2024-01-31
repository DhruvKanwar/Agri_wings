<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CropPrice extends Model
{
    use HasFactory;

    protected $table = 'crop_prices';

    protected $fillable = [
        'crop_id',
        'crop_name',
        'state',
        'state_price',
        'base_price',
        'status',
        'saved_by_id', 'saved_by_name', 'updated_by_id', 'updated_by_name', 'created_at', 'updated_at'


        // Add more fields if needed
    ];
}
