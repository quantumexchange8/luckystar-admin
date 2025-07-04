<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscriber extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'meta_login',
        'meta_account_type',
        'master_login',
        'master_account_type',
        'type',
        'subscriber_number',
        'initial_amount',
        'subscription_period',
        'subscription_period_unit',
        'completed_at',
        'settlement_period',
        'settlement_period_unit',
        'settlement_start_at',
        'settlement_end_at',
        'status',
        'remarks',
        'approval_at',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'settlement_start_at' => 'datetime',
            'settlement_end_at' => 'datetime',
            'approval_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    // Relations
    public function trading_master(): BelongsTo
    {
        return $this->belongsTo(TradingMaster::class, 'master_login', 'meta_login');
    }

    public function trading_account(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'meta_login', 'meta_login');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
