<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdersTimeline extends Model
{
    protected $table = 'services_timeline'; // Specify the table name

    protected $fillable = [
        'created_by_id',
        'created_by',
        'updated_by_id',
        'updated_by',
        'order_date',
        'assign_created_by_id',
        'assign_created_by',
        'assign_date',
        'aknowledged_created_by_id',
        'aknowledged_created_by',
        'aknowledged_date',
        'spray_started_created_by_id',
        'spray_started_created_by',
        'spray_started_date',
        'noc_image',
        'payment_proof_image',
        'signature_string',
        'farmer_image',
        'chemical_id',
        'fresh_water',
        'farmer_available',
        'payment_received_created_by_id',
        'payment_received_created_by',
        'payment_received_date',
        'payment_delivered_created_by_id',
        'payment_delivered_created_by',
        'payment_delivered_date',
        'payment_cancel_created_by_id',
        'payment_cancel_created_by',
        'payment_cancel_date'
    ];

    // Add any additional methods or relationships here if needed
}
