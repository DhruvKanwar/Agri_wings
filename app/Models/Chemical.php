<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chemical extends Model
{
    use HasFactory;
    protected $fillable = [
        'chemical_name',
        'saved_by_id',
        'saved_by_name',
        'updated_by_id',
        'updated_by_name',
        'status',
    ];
}


