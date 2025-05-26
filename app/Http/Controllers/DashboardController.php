<?php

namespace App\Http\Controllers;

use App\Models\Kyc;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getPendingCounts()
    {
        $pendingKYC = Kyc::where('kyc_status', 'pending')->count();

        return response()->json([
            'pendingKYC' => $pendingKYC,
        ]);
    }
}
