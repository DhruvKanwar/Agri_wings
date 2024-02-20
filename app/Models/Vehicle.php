<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'owned_by',
        'type',
        'registration_no',
        'chassis_no',
        'engine_no',
        'manufacturer',
        'year_of_make',
        'assigned_to_operator',
        'status',
        'saved_by_id',
        'saved_by_name',
        'updated_by_id',
        'updated_by_name',
        'created_at',
        'updated_at'
    ];

  

    public function AssetOperatorDetail()
    {
        return $this->belongsTo(AssetOperator::class, 'id');
    }
}
