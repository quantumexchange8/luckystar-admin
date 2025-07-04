<?php

namespace App\Http\Controllers;

use App\Models\Kyc;
use App\Models\Subscriber;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getPendingCounts()
    {
        $pendingKYC = Kyc::where('kyc_status', 'pending')->count();
        $pendingSubscriber = Subscriber::where('status', 'pending')->count();

        return response()->json([
            'pendingKYC' => $pendingKYC,
            'pendingSubscriberCounts' => $pendingSubscriber
        ]);
    }
}
