<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TopUpProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_name',
        'type',
        'payment_app_name',
        'payment_url',
        'secret_key',
        'secondary_key',
        'account_number',
        'bank_address',
        'swift_code',
        'country_id',
        'currency',
        'status',
    ];
}
