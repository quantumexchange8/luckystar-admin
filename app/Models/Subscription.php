<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subscriber_id',
        'subscription_amount',
        'real_fund',
        'demo_fund',
        'subscription_number',
        'terminated_at',
    ];

    protected function casts(): array
    {
        return [
            'terminated_at' => 'datetime',
        ];
    }
}
