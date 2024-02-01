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
        'saved_by_id', 'saved_by_name', 'updated_by_id', 'updated_by_name', 'created_at', 'updated_at'


        // Add more fields if needed
    ];
}


// INSERT INTO crops (crop_name) VALUES
// ('COTTON'),
// ('PADDY'),
// ('CLUSTER BEAN'),
// ('BARLEY'),
// ('BEANS'),
// ('BENGAL GRAM'),
// ('BITTER GOURD'),
// ('BLACK GOURD'),
// ('BLACK GRAM'),
// ('BRINJAL'),
// ('CABBAGE'),
// ('CABBAGE/CAULIFLOWER'),
// ('CAPSICUM'),
// ('CHICK PEA'),
// ('CHILLIES'),
// ('COLE CROPS'),
// ('CORN'),
// ('CUCUMBER'),
// ('CUCURBITS'),
// ('CUMIN'),
// ('DIRECT SEEDED RICE'),
// ('GREEN GRAM'),
// ('GROUNDNUT'),
// ('MAIZE'),
// ('MUSTARD'),
// ('OKRA'),
// ('ONION'),
// ('POTATO'),
// ('RED GRAM'),
// ('RIDGE GOURD'),
// ('SOYBEAN'),
// ('SUGARCANE'),
// ('TOMATO'),
// ('TURMERIC'),
// ('WATERMELON'),
// ('WHEAT');
