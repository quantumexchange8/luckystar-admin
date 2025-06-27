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

    public function adjustment()
    {
        return Inertia::render('Report/Transaction/Adjustment/AdjustmentListing');
    }

    public function get_adjustment_listing_data(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $type = $request->get('type');

            $query = Transaction::query()
                ->with([
                    'user:id,first_name,last_name,email,upline_id',
                    'from_wallet:id,type,address,currency_symbol',
                    'to_wallet:id,type,address,currency_symbol',
                ])
                ->where('transaction_type', $type) // if params is cash_in, filter cashin else cash_out
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

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->latest();
            }

            $adjustments = $query->paginate($data['rows']);

            $totalAdjustmentAmount = (clone $query)
                ->sum('amount');

            $adjustmentCounts = (clone $query)
                ->count();

            return response()->json([
                'success' => true,
                'data' => $adjustments,
                'totalAdjustmentAmount' => $totalAdjustmentAmount,
                'adjustmentCounts' => $adjustmentCounts,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function redemption()
    {
        return Inertia::render('Report/Transaction/Redemption/RedemptionListing');
    }

    public function get_redemption_listing_data(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = Transaction::query()
                ->with([
                    'user:id,first_name,last_name,email,upline_id',
                    'from_wallet:id,type,address,currency_symbol',
                    'to_wallet:id,type,address,currency_symbol',
                ])
                ->where('transaction_type', 'redeem')
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

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->latest();
            }

            $redemptions = $query->paginate($data['rows']);

            $totalRedemptionAmount = (clone $query)
                ->sum('amount');

            $redemptionCounts = (clone $query)
                ->count();

            return response()->json([
                'success' => true,
                'data' => $redemptions,
                'totalRedemptionAmount' => $totalRedemptionAmount,
                'redemptionCounts' => $redemptionCounts,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function transfer()
    {
        return Inertia::render('Report/Transaction/Transfer/TransferListing');
    }

    public function get_transfer_listing_data(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = Transaction::query()
                ->with([
                    'user:id,first_name,last_name,email,upline_id',
                    'from_wallet:id,type,address,currency_symbol',
                    'to_wallet:id,type,address,currency_symbol',
                ])
                ->where('transaction_type', 'transfer')
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

            $totalTransferAmount = (clone $query)
                ->sum('amount');

            $transferCounts = (clone $query)
                ->count();

            return response()->json([
                'success' => true,
                'data' => $withdrawals,
                'totalTransferAmount' => $totalTransferAmount,
                'transferCounts' => $transferCounts,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }
}
