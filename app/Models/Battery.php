<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Battery extends Model
{
    use HasFactory;
    protected $fillable = [
        'battery_code',
        'battery_type',
        'status',
        'battery_pair',
        'assigned_status',
        'assigned_date',
        'battery_id',
        'saved_by_id', 'saved_by_name', 'updated_by_id', 'updated_by_name', 'created_at', 'updated_at'
        // Add more fillable fields if needed
    ];
}
