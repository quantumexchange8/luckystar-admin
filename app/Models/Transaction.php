<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $guarded = [
        'id',
    ];

    protected function casts(): array
    {
        return [
            'approval_at' => 'datetime',
        ];
    }

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function approval_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function from_wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id', 'id');
    }

    public function to_wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id', 'id');
    }
}
