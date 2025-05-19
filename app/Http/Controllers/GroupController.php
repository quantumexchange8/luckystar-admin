<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupHasUser;
use App\Models\GroupRankSetting;
use App\Models\TradingSubscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\GroupService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class GroupController extends Controller
{
    protected GroupService $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function index()
    {
        return Inertia::render('Group/GroupListing', [
            'groupsCount' => Group::count(),
        ]);
    }

    public function getGroupsData(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = Group::with([
                'group_leader:id,first_name,last_name,email,upline_id,hierarchyList',
                'child_groups:id,parent_group_id',
                'group_has_user'
            ])->withCount('group_has_user');

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'LIKE', '%' . $keyword . '%')
                        ->orWhereHas('group_leader', function ($query) use ($keyword) {
                            $query->where('first_name', 'LIKE', '%' . $keyword . '%')
                                ->orWhere('last_name', 'LIKE', '%' . $keyword . '%')
                                ->orWhere('email', 'LIKE', '%' . $keyword . '%');
                        });
                });
            }

            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            $groups = $query->paginate($data['rows']);

            $hasDateFilter = !empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value']);

            $start_date = null;
            $end_date = null;

            if ($hasDateFilter) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();
            }

            foreach ($groups as $group) {
                $userIds = $group->group_has_user->pluck('user_id')->toArray();

                $group->wallet_top_up = $this->sumTransactionAmount($userIds, 'cash_wallet', 'top_up', 'success', 'approval_at', $hasDateFilter, $start_date, $end_date);
                $group->wallet_withdrawal = $this->sumTransactionAmount($userIds, 'cash_wallet', 'withdrawal', 'success', 'approval_at', $hasDateFilter, $start_date, $end_date);
                $group->account_deposit = $this->sumTransactionAmount($userIds, 'trading_account', 'deposit', 'success', 'approval_at', $hasDateFilter, $start_date, $end_date);
                $group->account_withdrawal = $this->sumTransactionAmount($userIds, 'trading_account', 'withdrawal', 'success', 'approval_at', $hasDateFilter, $start_date, $end_date);

                $group->active_capital = TradingSubscription::whereIn('user_id', $userIds)
                    ->where('status', 'active')
                    ->when($hasDateFilter, function ($query) use ($start_date, $end_date) {
                        $query->whereBetween('created_at', [$start_date, $end_date]);
                    })
                    ->sum('subscription_amount');
            }

            return response()->json([
                'success' => true,
                'data' => $groups,
                'totalWalletTopUp' => (float) $groups->sum('wallet_top_up'),
                'totalWalletWithdrawal' => (float) $groups->sum('wallet_withdrawal'),
                'totalActiveCapital' => (float) $groups->sum('active_capital'),
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    private function sumTransactionAmount($userIds, $category, $type, $status, $dateField, $hasDateFilter, $start_date = null, $end_date = null)
    {
        return Transaction::whereIn('user_id', $userIds)
            ->where('category', $category)
            ->where('transaction_type', $type)
            ->where('status', $status)
            ->when($hasDateFilter, function ($query) use ($start_date, $end_date, $dateField) {
                $query->whereBetween($dateField, [$start_date, $end_date]);
            })
            ->sum('amount');
    }

    public function addGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'regex:/^[a-zA-Z0-9\p{Han}. ]+$/u', 'max:255'],
            'color' => ['required'],
            'leader' => ['required'],
        ])->setAttributeNames([
            'name' => trans('public.name'),
            'color' => trans('public.color'),
            'leader' => trans('public.leader'),
        ]);
        $validator->validate();

        $leader_id = $request->leader['id'];
        $group_of_leader = GroupHasUser::where('user_id', $leader_id)
            ->first();

        if ($group_of_leader) {
            $parent_group_id = $group_of_leader->group_id;
        } else {
            $parent_group_id = Group::first()->id;
        }

        $group = Group::create([
            'name' => $request->name,
            'color' => strtoupper($request->color),
            'group_leader_id' => $leader_id,
            'parent_group_id' => $parent_group_id,
            'edited_by' => Auth::id(),
        ]);

        $group_id = $group->id;
        $children_ids = User::find($leader_id)->getChildrenIds();
        $children_ids[] = $leader_id;

        foreach ($children_ids as $child_id) {
            $this->groupService->updateUserGroup($group_id, $child_id);
        }

        if ($request->rank_settings) {
            // Fetch default group rank settings and key them by 'id' for easier comparison
            $default = GroupRankSetting::where('group_id', 1)
                ->get()
                ->keyBy('id');

            // Start with an empty array to hold merged ranks
            $mergedRanks = [];

            // Overwrite or append new rank settings from the request
            foreach ($request->rank_settings as $rankId => $rankData) {
                // Get the existing rank from default or an empty array if not found
                $existingRank = isset($default[$rankId]) ? $default[$rankId]->toArray() : [];

                // Merge request data with default data (if any), ensuring all fields are accounted for
                $mergedRanks[$rankId] = array_merge($existingRank, $rankData);
            }

            // Add any remaining default ranks that are not in the request
            foreach ($default as $rankId => $existingRank) {
                if (!isset($mergedRanks[$rankId])) {
                    $mergedRanks[$rankId] = $existingRank->toArray();
                }
            }

            ksort($mergedRanks);

            $i = 1;
            $newRankSettings = [];

            foreach ($mergedRanks as $rankId => $rankData) {
                $rankSetting = GroupRankSetting::updateOrCreate(
                    [
                        'group_id' => $group_id,
                        'rank_name' => $rankData['rank_name']
                    ],
                    [
                        'group_id' => $group_id,
                        'rank_name' => $rankData['rank_name'],
                        'rank_position' => $i,
                        'lot_rebate_currency' => $rankData['lot_rebate_currency'] ?? 'USD',
                        'lot_rebate_amount' => $rankData['lot_rebate_amount'],
                        'min_direct_referral' => $rankData['min_direct_referral'] ?? null,
                        'group_sales_currency' => $rankData['group_sales_currency'] ?? null,
                        'max_capped_per_line' => $rankData['min_group_sales'] / ($rankData['min_direct_referral'] ?? 1),
                        'min_group_sales' => $rankData['min_group_sales'] ?? 0,
                        'edited_by' => Auth::id(),
                    ]
                );

                $newRankSettings[$rankId] = $rankSetting->id;

                $i++;
            }

            // Second pass to update min_direct_referral_rank_id with new rank ids
            foreach ($mergedRanks as $rankId => $rankData) {
                if (isset($rankData['min_direct_referral_rank_id'])) {
                    $newRankId = $newRankSettings[$rankData['min_direct_referral_rank_id']] ?? null;

                    if ($newRankId) {
                        GroupRankSetting::where('group_id', $group_id)
                            ->where('rank_name', $rankData['rank_name'])
                            ->update([
                                'min_direct_referral_rank_id' => $newRankId
                            ]);
                    }
                }
            }
        }

        return back()->with('toast', [
            'title' => trans('public.success'),
            'message' => trans('public.toast_create_group_success'),
            'type' => 'success',
        ]);
    }
}
