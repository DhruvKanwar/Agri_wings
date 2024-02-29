<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crop extends Model
{
    use HasFactory;

    protected $table = 'crops';
    protected $fillable = [
        'crop_name',
        'base_price',
        'status',
        'water_saved',
        'saved_by_id', 'saved_by_name', 'updated_by_id', 'updated_by_name', 'created_at', 'updated_at'


        // Add more fields if needed
    ];
}

// INSERT INTO crops (crop_name, water_saved)
// VALUES
//     ('COTTON', 190),
//     ('PADDY', 190),
//     ('CLUSTER BEAN', 190),
//     ('BARLEY', 190),
//     ('BEANS', 190),
//     ('BENGAL GRAM', 190),
//     ('BITTER GOURD', 190),
//     ('BLACK GOURD', 190),
//     ('BLACK GRAM', 190),
//     ('BRINJAL', 190),
//     ('CABBAGE', 190),
//     ('CABBAGE/CAULIFLOWER', 190),
//     ('CAPSICUM', 190),
//     ('CHICK PEA', 190),
//     ('CHILLIES', 190),
//     ('COLE CROPS', 190),
//     ('CORN', 190),
//     ('CUCUMBER', 190),
//     ('CUCURBITS', 190),
//     ('CUMIN', 190),
//     ('DIRECT SEEDED RICE', 190),
//     ('GREEN GRAM', 190),
//     ('GROUNDNUT', 190),
//     ('MUSTARD', 190),
//     ('OKRA', 190),
//     ('ONION', 190),
//     ('POTATO', 190),
//     ('RED GRAM', 190),
//     ('RIDGE GOURD', 190),
//     ('SOYBEAN', 190),
//     ('TOMATO', 190),
//     ('TURMERIC', 190),
//     ('WATERMELON', 190),
//     ('WHEAT', 190),
//     ('MAIZE', 290),
//     ('SUGARCANE', 290);

