<?php

namespace App\Http\Controllers;

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

        return response()->json([
            'uplines' => $uplines,
        ]);
    }

}
