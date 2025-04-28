<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TradingMaster extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'master_name',
        'meta_login',
        'category',
        'type',
        'min_investment',
        'sharing_profit',
        'sa_profit',
        'market_profit',
        'estimated_monthly_returns',
        'estimated_lot_size',
        'subscription_fee',
        'extra_fund',
        'total_fund',
        'max_drawdown',
        'settlement_period_type',
        'settlement_period',
        'join_period_type',
        'join_period',
        'signal_status',
        'can_top_up',
        'can_revoke',
        'visibility_type',
        'status',
        'handle_by',
    ];

    // Relations
    public function ongoing_subscriptions(): HasMany
    {
        return $this->hasMany(TradingSubscription::class, 'master_meta_login', 'meta_login')->where('status', 'active');
    }
}
