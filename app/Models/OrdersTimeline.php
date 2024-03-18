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
        'completed_date',
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
        'farmer_signature',
        'farmer_image',
        'payment_received_created_by_id',
        'payment_received_created_by',
        'payment_received_date',
        'delivered_created_by_id',
        'delivered_created_by',
        'delivered_date',
        'cancel_created_by_id',
        'cancel_created_by',
        'cancel_date',
        'chemical_used_ids',
        'farmer_available',
        'fresh_water',
        'refund_image',
        'farmer_refund_signature'

    ];

    // Add any additional methods or relationships here if needed
}
