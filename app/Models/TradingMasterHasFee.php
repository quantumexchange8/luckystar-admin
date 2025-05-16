<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TradingMasterHasFee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'trading_master_id',
        'meta_login',
        'management_days',
        'management_percentage',
    ];
}
