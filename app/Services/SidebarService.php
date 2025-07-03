<?php

namespace App\Services;

use App\Models\Kyc;
use App\Models\Subscriber;
use App\Models\Transaction;

class SidebarService
{
    public function getPendingKYC(): int
    {
        $query = Kyc::query()
            ->where('kyc_status', 'pending');

        return $query->count();
    }

    public function getPendingSubscriberCounts(): int
    {
        $query = Subscriber::query()
            ->where('status', 'pending');

        return $query->count();
    }
}
