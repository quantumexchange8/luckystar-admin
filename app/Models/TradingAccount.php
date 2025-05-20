<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TradingAccount extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    // Relations
    public function trading_user(): BelongsTo
    {
        return $this->belongsTo(TradingUser::class, 'meta_login', 'meta_login');
    }

    public function account_type(): BelongsTo
    {
        return $this->belongsTo(AccountType::class, 'account_type_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function active_subscriptions(): HasMany
    {
        return $this->hasMany(TradingSubscription::class, 'meta_login', 'meta_login')->where('status', 'active');
    }
}
