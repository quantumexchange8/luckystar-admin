<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTypeHasLeverage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_type_id',
        'setting_leverage_id',
    ];
}
