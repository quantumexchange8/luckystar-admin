<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TradingMaster extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'master_name',
        'trader_name',
        'meta_login',
        'account_type_id',
        'leverage',
        'category',
        'type',
        'sharing_profit',
        'market_profit',
        'company_profit',
        'minimum_investment',
        'subscription_fee',
        'estimated_lot',
        'estimated_monthly_return',
        'max_drawdown',
        'cut_loss',
        'additional_capital',
        'additional_investors',
        'investment_period',
        'investment_period_type',
        'settlement_period',
        'settlement_period_type',
        'can_top_up',
        'can_terminate',
        'status',
    ];

    // Relations
    public function ongoing_subscriptions(): HasMany
    {
        return $this->hasMany(TradingSubscription::class, 'master_meta_login', 'meta_login')->where('status', 'active');
    }
}
