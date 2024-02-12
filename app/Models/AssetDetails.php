<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDetails extends Model
{
    use HasFactory;
    protected $fillable = [
        'asset_name', 'asset_id', 'model', 'uin', 'capacity', 'mfg_year', 'battery_ids',
        'assigned_status', 'assigned_date','status', 'saved_by_id', 'saved_by_name', 'updated_by_id', 'updated_by_name','created_at','updated_at'
    ];
}
