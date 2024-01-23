<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegionalClient extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'base_client_id',
        'regional_client_name',
        'state',
        'gst_no',
        'address',
        'remarks',
        'status',
        'saved_by_id',
        'saved_by_name',
        'updated_by_id',
        'updated_by_name',
        'created_at', 'updated_at'
    ];
}
