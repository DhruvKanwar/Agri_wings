<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseClient extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'base_clients';
    protected $fillable = [
        'client_name',
        'pan_no',
        'cin',
        'registration_address',
        'account_no',
        'ifsc',
        'bank_name',
        'branch_name',
        'upi_id',
        'gst_nature',
        'signature_name',
        'qr_code',
        'sign_img',
        'logo_img',
        'phone_number',
        'status',
        'saved_by_id', 'saved_by_name', 'updated_by_id', 'updated_by_name', 'created_at', 'updated_at'
    ];

    public function regionalClients()
    {
        return $this->hasMany(RegionalClient::class, 'base_client_id');
    }

}
