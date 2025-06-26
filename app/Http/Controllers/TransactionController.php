<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function top_up()
    {
        return Inertia::render('Report/Transaction/TopUp/TopUpListing');
    }

    public function get_top_up_listing_data(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = Transaction::query()
                ->with([
                    'user:id,first_name,last_name,email,upline_id',
                    'from_wallet:id,type,address,currency_symbol',
                    'to_wallet:id,type,address,currency_symbol',
                ])
                ->whereIn('transaction_type', ['top_up', 'top_up_capital'])
                ->whereNot('status', 'pending');


            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($query) use ($keyword) {
                        $query->where(function ($q) use ($keyword) {
                            $q->whereRaw("CONCAT(`first_name`, ' ', `last_name`) LIKE ?", ['%' . $keyword . '%'])
                                ->orWhere('email', 'like', '%' . $keyword . '%')
                                ->orWhere('username', 'like', '%' . $keyword . '%');
                        });
                    })->orWhere('transaction_number', 'like', '%' . $keyword . '%');
                });
            }

            //date filter
            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay(); //add day to ensure capture entire day
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            //status filter
            if ($data['filters']['status']['value']) {
                $query->where('status', $data['filters']['status']['value']);
            }

            // type filter
            if ($data['filters']['type']['value']) {
                $query->where('transaction_type', $data['filters']['type']['value']);
            }

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->latest();
            }

            $withdrawals = $query->paginate($data['rows']);

            $totalTopUpAmount = (clone $query)
                ->sum('amount');

            $topUpCounts = (clone $query)
                ->count();

            return response()->json([
                'success' => true,
                'data' => $withdrawals,
                'totalTopUpAmount' => $totalTopUpAmount,
                'topUpCounts' => $topUpCounts,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function withdrawal()
    {
        return Inertia::render('Report/Transaction/Withdrawal/WithdrawalListing');
    }

    public function get_withdrawal_listing_data(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = Transaction::query()
                ->with([
                    'user:id,first_name,last_name,email,upline_id',
                ])
                ->where('transaction_type', 'withdrawal')
                ->whereNot('status', 'pending');

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($query) use ($keyword) {
                        $query->where(function ($q) use ($keyword) {
                            $q->whereRaw("CONCAT(`first_name`, ' ', `last_name`) LIKE ?", ['%' . $keyword . '%'])
                                ->orWhere('email', 'like', '%' . $keyword . '%')
                                ->orWhere('username', 'like', '%' . $keyword . '%');
                        });
                    })->orWhere('transaction_number', 'like', '%' . $keyword . '%');
                });
            }

            //date filter
            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay(); //add day to ensure capture entire day
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            //status filter
            if ($data['filters']['status']['value']) {
                $query->where('status', $data['filters']['status']['value']);
            }


            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->latest();
            }

            $withdrawals = $query->paginate($data['rows']);

            $totalWithdrawalAmount = (clone $query)
                ->sum('amount');

            $withdrawalCounts = (clone $query)
                ->count();

            return response()->json([
                'success' => true,
                'data' => $withdrawals,
                'totalWithdrawalAmount' => $totalWithdrawalAmount,
                'withdrawalCounts' => $withdrawalCounts,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }
}
