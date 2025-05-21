<?php

namespace App\Http\Controllers;

use App\Exports\InvestmentExport;
use App\Models\TradingSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;

class InvestmentController extends Controller
{
    public function index()
    {
        return Inertia::render('Investment/InvestmentListing');
    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function getInvestmentsData(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $status = $data['filters']['status']['value'];

            $query = TradingSubscription::with([
                'user',
                'user.upline',
                'user.group.group:id,name,color',
                'trading_master',
                'trading_master.account_type',
            ])
                ->where('status', $status);

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($query) use ($keyword) {
                        $query->where(function ($q) use ($keyword) {
                            $q->where('first_name', 'like', '%' . $keyword . '%')
                                ->orWhere('last_name', 'like', '%' . $keyword . '%')
                                ->orWhere('email', 'like', '%' . $keyword . '%')
                                ->orWhere('username', 'like', '%' . $keyword . '%');
                        });
                    })->orWhere('meta_login', 'like', '%' . $keyword . '%')
                        ->orWhere('subscription_number', 'like', '%' . $keyword . '%')
                        ->orWhere('master_meta_login', 'like', '%' . $keyword . '%');
                });
            }

            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                if ($status == 'revoked') {
                    $query->whereBetween('terminated_at', [$start_date, $end_date]);
                } else {
                    $query->whereBetween('approval_at', [$start_date, $end_date]);
                }
            }

            if (!empty($data['filters']['group_id']['value'])) {
                $query->whereHas('user.group', function ($q) use ($data) {
                    $q->where('group_id', $data['filters']['group_id']['value']['id']);
                });
            }

            if (!empty($data['filters']['strategy_login']['value'])) {
                $query->where('master_meta_login', $data['filters']['strategy_login']['value']['meta_login']);
            }

            if (!empty($data['filters']['referrers']['value'])) {
                $query->whereHas('user', function ($q) use ($data) {
                    $selected_referrers = $data['filters']['referrers']['value'];
                    $userIds = array_column($selected_referrers, 'user_id');

                    $q->whereIn('upline_id', $userIds);
                });
            }

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';

                if ($data['sortField'] == 'days') {
                    $query->orderBy('approval_at', $data['sortOrder'] == 0 ? 'asc' : 'desc');
                } else {
                    $query->orderBy($data['sortField'], $order);
                }
            } else {
                if ($status == 'revoked') {
                    $query->orderByDesc('terminated_at');
                } else {
                    $query->orderByDesc('approval_at');
                }
            }

            $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();
            $field = $status == 'revoked' ? 'terminated_at' : 'approval_at';

            $last_month_investors = (clone $query)
                ->whereDate($field, '<=', $endOfLastMonth)
                ->distinct('meta_login')
                ->count();

            $total_investors = (clone $query)
                ->distinct('meta_login')
                ->count();

            $investors_trend = $last_month_investors > 0
                ? (($total_investors - $last_month_investors) / $last_month_investors) * 100
                : ($total_investors > 0 ? 100 : 0);

            $last_month_fund_size = (clone $query)
                ->whereDate($field, '<=', $endOfLastMonth)
                ->sum('subscription_amount');

            $fund_size = (clone $query)
                ->sum('subscription_amount');

            $fund_size_trend = $last_month_fund_size > 0
                ? (($fund_size - $last_month_fund_size) / $last_month_fund_size) * 100
                : ($fund_size > 0 ? 100 : 0);

            if ($request->has('exportStatus')) {
                return Excel::download(new InvestmentExport($query, $status), now() . '-investment-report.xlsx');
            }

            $transactions = $query->paginate($data['rows']);

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'investorsCount' => (float) $total_investors,
                'investorsTrend' => (float) $investors_trend,
                'fundSize' => (float) $fund_size,
                'fundSizeTrend' => (float) $fund_size_trend,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }
}
