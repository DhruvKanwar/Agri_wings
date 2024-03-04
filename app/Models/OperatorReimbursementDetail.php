<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatorReimbursementDetail extends Model
{
    use HasFactory;
    protected $table = 'operator_reimbursement_details'; 
    protected $fillable = [
        'unid', 'category', 'bill_no', 'from_date', 'to_date', 'bill_amount', 'claimed_amount', 'remarks', 'attachment', 'status', 'user_id'
    ];
}
