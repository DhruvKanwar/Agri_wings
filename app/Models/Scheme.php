<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Scheme extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'type',
        'crop_name',
        'scheme_code',
        'scheme_name',
        'crop_id',
        'period_from',
        'period_to',
        'crop_base_price',
        'discount_price',
        'min_acreage',
        'max_acreage',
        'client_id',
        'status',
        'remarks',
        'saved_by_id',
        'saved_by_name',
        'updated_by_id',
        'updated_by_name',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // Define relationships if necessary
    // For example, if 'crop_id' and 'client_id' are foreign keys, you can define relationships here.

    // Example:
    // public function crop()
    // {
    //     return $this->belongsTo(Crop::class, 'crop_id');
    // }

    // public function client()
    // {
    //     return $this->belongsTo(Client::class, 'client_id');
    // }
}
