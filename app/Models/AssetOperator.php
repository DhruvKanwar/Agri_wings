<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetOperator extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'name',
        'phone',
        'rpc_no',
        'dl_no',
        'aadhaar_no',
        'dl_img',
        'aadhaar_img',
        'start_date',
        'end_date',
        'user_id',
        'user_password',
        'vehicle_id',
        'status',
        'saved_by_id',
        'saved_by_name',
        'updated_by_id',
        'updated_by_name',
        'created_at',
        'updated_at'
    ];
}
