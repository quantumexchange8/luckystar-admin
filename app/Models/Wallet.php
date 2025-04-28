<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    // Relations
    public function success_withdrawals(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_wallet_id', 'id')->where('transaction_type', 'withdrawal')->where('status', 'success');
    }
}
