<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountTypeHasLeverage extends Model
{
    protected $fillable = [
        'account_type_id',
        'setting_leverage_id',
    ];
    
    public function setting_leverage(): BelongsTo
    {
        return $this->belongsTo(SettingLeverage::class, 'setting_leverage_id', 'id');
    }

}
