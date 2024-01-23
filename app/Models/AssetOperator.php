<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetOperator extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'code',
        'name',
        'phone',
        'rpc_no',
        'rpc_img',
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


    public function VehicleDetails()
    {
        return $this->belongsTo('App\Models\Vehicle', 'vehicle_id', 'id');
    }

    public function UserDetails()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
}
