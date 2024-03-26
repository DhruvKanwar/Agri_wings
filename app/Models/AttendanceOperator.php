<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceOperator extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'user_name',
        'user_mobile_no',
        'in',
        'out',
        'date',
        'working_hours',
        'remarks',
    ];

}
