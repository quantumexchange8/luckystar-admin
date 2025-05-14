<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountType extends Model
{
    use SoftDeletes;

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    // Relations
    public function trading_accounts(): HasMany
    {
        return $this->hasMany(TradingAccount::class, 'account_type_id', 'id');
    }

    public function account_leverages(): HasMany
    {
        return $this->hasMany(AccountTypeHasLeverage::class, 'account_type_id', 'id');
    }

    
}
