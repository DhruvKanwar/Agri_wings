<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ter extends Model
{
    use HasFactory;
    protected $table = 'ter';
    protected $fillable = [
        'user_id',
        'from_date',
        'to_date',
        'da_amount',
        'total_attendance',
        'total_claimed_amount',
        'total_category_amount',
        'da_limit',
        'category_ids',
        'submit_date',
        'hr_updated_date',
        'remarks',
        'status', 'operator_id'
    ];

  
    public function operatorReimbursement()
    {
        return $this->hasMany('App\Models\OperatorReimbursementDetail', 'unid', 'id');
    }

    public function assetOperator()
    {
        return $this->belongsTo(AssetOperator::class,'id', 'operator_id')
        ->select('name', 'phone', 'status')
        ->withTrashed();
    }
}
