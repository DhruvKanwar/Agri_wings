<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Services extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_type',
        'client_id',
        'farmer_name',
        'farmer_id',
        'spray_date',
        'crop_name',
        'crop_id',
        'requested_acreage',
        'sprayed_acreage',
        'farm_location',
        'scheme_ids',
        'total_discount',
        'extra_discount',
        'remarks',
        'amount_received',
        'total_amount',
        'refund_amount',
        'total_payable_amount',
        'order_status',
        'order_date',
        'delivery_date',
        'payment_status',
        'spray_status',
        'agriwings_discount',
        'client_discount',
        'added_amount',
        'asset_operator_id',
         'assigned_status',
        'assigned_date',
        'asset_id',
        'battery_ids',
        'order_accepted',
        'order_details_id',
     
    ];

      public function assetOperator()
    {
        return $this->belongsTo(AssetOperator::class, 'asset_operator_id');
    }

    public function asset()
    {
        return $this->belongsTo(AssetDetails::class, 'asset_id');
    }

    public function batteries()
    {
        return $this->hasMany(Battery::class, 'battery_id');
    }

    public function getBatteryIdsAttribute($value)
    {
        return explode(',', $value);
    }

}
