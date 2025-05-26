<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Models\AccountTypeHasLeverage;
use App\Models\GroupHasUser;
use App\Models\GroupRankSetting;
use App\Models\TradingMaster;
use App\Models\User;
use App\Models\Country;
use App\Models\Group;
use Illuminate\Http\Request;

class SelectOptionController extends Controller
{
    public function getCountries($returnAsArray = false)
    {
        $countries = Country::select('id', 'name', 'phone_code', 'iso2', 'emoji', 'translations', 'currency', 'currency_symbol')
            ->get();

        if ($returnAsArray) {
            return $countries;
        }

        return response()->json([
            'countries' => $countries,
        ]);
    }

    public function getGroups($returnAsArray = false)
    {
        $groups = Group::select('id', 'name', 'group_leader_id', 'color', 'parent_group_id')
            ->get();

        if ($returnAsArray) {
            return $groups;
        }

        return response()->json([
            'groups' => $groups,
        ]);
    }

    public function getUplines($returnAsArray = false)
    {
        $uplines = User::select('id', 'first_name', 'last_name', 'email', 'id_number')
            ->get();

        if ($returnAsArray) {
            return $uplines;
        }

        return response()->json([
            'uplines' => $uplines,
        ]);
    }

    public function getUplinesByGroup(Request $request)
    {
        $groupId = $request->input('group_id');

        $uplines = User::whereHas('group', function ($query) use ($groupId) {
                $query->where('group_id', $groupId);
            })
            ->select('id', 'first_name', 'last_name', 'email', 'id_number')
            ->get();

        // Add profile_photo URL to each upline
        foreach ($uplines as $upline) {
            $upline->profile_photo = $upline?->getFirstMediaUrl('profile_photo');
        }

        return response()->json([
            'uplines' => $uplines,
        ]);
    }

    public function getAvailableLeader()
    {
        $group_leader_ids = Group::pluck('group_leader_id')->toArray();

        $users = User::whereNotIn('role', ['super_admin', 'admin'])
            ->whereNotIn('id', $group_leader_ids)
            ->select('id', 'first_name', 'last_name', 'username')
            ->get()
            ->map(function($user) {
                $data = $user;
                $data['total_group_members'] = count($user->getChildrenIds());

                return $data;
            });

        return response()->json($users);
    }

    public function getSettingRanks()
    {
        $ranks = GroupRankSetting::select('id', 'rank_name', 'lot_rebate_amount', 'min_group_sales')
            ->where('group_id', 1)
            ->where('rank_position', '>', 1)
            ->get()
            ->map(function($rank) {
                $rank->lot_rebate_amount = intval($rank->lot_rebate_amount);
                $rank->min_group_sales = intval($rank->min_group_sales);
                return $rank;
            });

        return response()->json($ranks);
    }

    public function getLeverages($id)
    {
        $leverages = AccountTypeHasLeverage::with('setting_leverage')
            ->where('account_type_id', $id)
            ->get();

        return response()->json([
            'leverages' => $leverages,
        ]);
    }

    public function getGroupLeaders()
    {
        $groups = Group::with('group_leader')
            ->get();

        return response()->json($groups);
    }

    public function getGroupMembers(Request $request)
    {
        $groups = GroupHasUser::with('user:id,first_name,last_name')
            ->where('group_id', $request->group_id)
            ->get();

        return response()->json($groups);
    }

    public function getStrategies(Request $request)
    {
        $strategies = TradingMaster::get();

        return response()->json($strategies);
    }


    public function getAccountTypes()
    {
        $accountTypes = AccountType::get();

        return response()->json($accountTypes);
    }
}
