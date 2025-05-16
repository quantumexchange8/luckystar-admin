<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use App\Models\Wallet;
use App\Models\Country;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\AddMemberRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class MemberController extends Controller
{
    public function listing()
    {
        return Inertia::render('Member/Listing/MemberListing', [
            'groups' => (new SelectOptionController())->getGroups(true),
            'countries' => (new SelectOptionController())->getCountries(true),
        ]);
    }

    public function getMemberListingData(Request $request)
    {

        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true); //only() extract parameters in lazyEvent

            $query = User::with('group.group:id,name,color', 'country:id,name', 'upline:id,first_name,last_name,email,id_number', 'rank:id,rank_name')
                ->withSum('active_subscriptions', 'subscription_amount')
                ->whereNot('role', 'super_admin');

            $search = $data['filters']['global'];
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $keyword = $search;

                    $query->whereRaw("CONCAT(`first_name`, ' ', `last_name`) LIKE ?", ['%' . $keyword . '%'])
                        ->orWhere('email', 'like', '%' . $keyword . '%')
                        ->orWhere('id_number', 'like', '%' . $keyword . '%')

                        ->orWhereHas('upline', function ($q) use ($keyword) {
                            $q->whereRaw("CONCAT(`first_name`, ' ', `last_name`) LIKE ?", ['%' . $keyword . '%'])
                                ->orWhere('email', 'like', '%' . $keyword . '%')
                                ->orWhere('id_number', 'like', '%' . $keyword . '%');
                        });            
                });
            }

            if ($data['filters']['group_id']) {
                $query->whereHas('group', function ($query) use ($data) {
                    $query->where('group_id', $data['filters']['group_id']);
                });
            }
    
            if ($data['filters']['upline_id']) {
                $query->where('upline_id', $data['filters']['upline_id']);
            }

            if ($data['filters']['status']) {
                $query->where('status', $data['filters']['status']);
            }
    
            if ($data['filters']['kyc_status']) {
                $kycStatus = $data['filters']['kyc_status'];
    
                if ($kycStatus == 'verified') {
                    // Include users who have all KYC records verified
                    $query->whereHas('kycs', function ($kycQuery) {
                        $kycQuery->where('status', 'verified');
                    });
                    $query->where(function($query) {
                        $query->whereHas('kycs', function ($kycQuery) {
                            $kycQuery->where('status', 'verified');
                        })
                        ->orWhereDoesntHave('kycs');
                    });
                } elseif ($kycStatus == 'unverified') {
                    // Include users who either have no KYC records or have unverified KYC records
                    $query->whereDoesntHave('kycs', function ($kycQuery) {
                        $kycQuery->where('status', 'verified');
                    });
                    $query->orWhere(function ($query) {
                        $query->whereHas('kycs', function ($kycQuery) {
                            $kycQuery->where('status', 'unverified');
                        });
                    });
                }
            }
            
            // Handle sorting
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                if ($data['sortField'] === 'full_name') {
                    // Sort by first_name and last_name when sorting by full_name
                    $query->orderBy('first_name', $order)->orderBy('last_name', $order);
                } else {
                    // Sort by other fields directly
                    $query->orderBy($data['sortField'], $order);
                }
            } else {
                $query->orderByDesc('created_at');
            }
    
            // Handle pagination
            $rowsPerPage = $data['rows'] ?? 15; // Default to 15 if 'rows' not provided
            
            // // Export logic
            // if ($request->has('exportStatus') && $request->exportStatus) {
            //     $members = $query; // Fetch all members for export
            //     return Excel::download(new MemberListingExport($members), now() . '-members.xlsx');
            // }

            $query->select('id', 'first_name', 'last_name', 'username', 'email', 'id_number', 'country_id', 'upline_id', 'hierarchyList', 'role', 'setting_rank_id', 'status');

            $users = $query->paginate($rowsPerPage);

            foreach ($users as $user) {
                $country = $user->country ?? null;
                $group = $user->group->group ?? null;
                $upline = $user->upline ?? null;
                $rank = $user->rank ?? null;
            
                $user->capital = $user->active_subscriptions_sum_subscription_amount;
                $user->profile_photo = $user?->getFirstMediaUrl('profile_photo');

                $user->country_name = $country?->name;

                $user->group_id = $group?->id;
                $user->group_name = $group?->name;
                $user->group_color = $group?->color;
            
                $user->upline_id = $upline?->id;
                $user->upline_name = $upline?->full_name;
                $user->upline_email = $upline?->email;
                $user->upline_id_number = $upline?->id_number;
                $user->upline_profile_photo = $upline?->getFirstMediaUrl('profile_photo');

                $user->rank_id = $rank?->id;
                $user->rank_name = $rank?->rank_name;

                unset($user->active_subscriptions_sum_subscription_amount);
                unset($user->country);
                unset($user->group);
                unset($user->upline);
                unset($user->rank);
            }
            
        }

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function addNewMember(AddMemberRequest $request)
    {
        $upline_id = $request->upline['id'];
        $upline = User::find($upline_id);

        if(empty($upline->hierarchyList)) {
            $hierarchyList = "-" . $upline_id . "-";
        } else {
            $hierarchyList = $upline->hierarchyList . $upline_id . "-";
        }

        $dial_code = $request->dial_code;
        $country = Country::find($dial_code['id']);
        $default_ib_id = User::where('id_number', 'IB00000')->first()->id;

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'email' => $request->email,
            'dial_code' => $dial_code['phone_code'],
            'phone' => $request->phone,
            'phone_number' => $request->phone_number,
            'upline_id' => $upline_id,
            'country_id' => $country->id,
            'nationality' => $country->nationality,
            'hierarchyList' => $hierarchyList,
            'password' => Hash::make($request->password),
            'role' => $upline_id == $default_ib_id ? 'ib' : 'member',
        ]);

        $user->setReferralId();

        // Assign the appropriate role based on the upline ID or default conditions
        $user->assignRole($upline_id == $default_ib_id ? 'ib' : 'member');

        $id_no = ($user->role == 'ib' ? 'IB' : 'MB') . Str::padLeft($user->id - 2, 5, "0");
        $user->id_number = $id_no;
        $user->save();

        if ($upline->group) {
            $user->assignedGroup($upline->group->group_id);
        }

        if ($user->role == 'ib') {
            Wallet::create(attributes: [
                'user_id' => $user->id,
                'type' => 'rebate_wallet',
                'address' => str_replace('IB', 'RB', $user->id_number),
                'balance' => 0
            ]);

            // $uplineRebates = RebateAllocation::where('user_id', $user->upline_id)->get();

            // foreach ($uplineRebates as $uplineRebate) {
            //     RebateAllocation::create([
            //         'user_id' => $user->id,
            //         'account_type_id' => $uplineRebate->account_type_id,
            //         'symbol_group_id' => $uplineRebate->symbol_group_id,
            //         'amount' => 0,
            //         'edited_by' => Auth::id(),
            //     ]);
            // }
        }

        return back()->with('toast', [
            'title' => trans("public.toast_create_member_success"),
            'type' => 'success',
        ]);
    }

    public function updateMemberStatus(Request $request)
    {
        $user = User::find($request->id);

        $user->status = $user->status == 'active' ? 'inactive' : 'active';
        $user->save();

        return back()->with('toast', [
            'title' => $user->status == 'active' ? trans("public.toast_member_has_activated") : trans("public.toast_member_has_deactivated"),
            'type' => 'success',
        ]);
    }

    public function getAvailableUplines(Request $request)
    {
        $role = $request->input('role', ['ib', 'member']);
    
        $memberId = $request->input('id');

        // Fetch the member and get their children (downline) IDs
        $member = User::findOrFail($memberId);
        $excludedIds = $member->getChildrenIds();
        $excludedIds[] = $memberId;
    
        // Fetch uplines who are not in the excluded list
        $uplines = User::whereIn('role', (array) $role)
            ->whereNotIn('id', $excludedIds)
            ->get()
            ->map(function ($user) {
                return [
                    'value' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'id_number' => $user->id_number,
                    // 'profile_photo' => $user->getFirstMediaUrl('profile_photo')
                ];
            });
    
        // Return the uplines as JSON
        return response()->json([
            'uplines' => $uplines
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', Password::min(8)->letters()->numbers()->symbols()->mixedCase(), 'confirmed'],
            'password_confirmation' => ['required','same:password'],
        ])->setAttributeNames([
            'password' => trans('public.password'),
            'password_confirmation' => trans('public.confirm_password')
        ]);
        $validator->validate();

        $user = User::find($request->id);
        $user->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
        ]);

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_reset_password_success'),
            'type' => 'success'
        ]);

    }

    public function detail($id_number)
    {
        $user = User::where('id_number', $id_number)->select('id')->first();

        return Inertia::render('Member/Listing/MemberDetail/MemberDetail', [
            'user' => $user
        ]);
    }

    public function getUserData(Request $request)
    {
        $user = User::findOrFail($request->id);

        $userData = [
            'id' => $user->id,
            'name' => $user->full_name,
            'email' => $user->email,
            'dial_code' => $user->dial_code,
            'phone' => $user->phone,
            'phone_number' => $user->phone_number,
            'country' => $user->country->name ?? null,
            'nationality' => $user->nationality,
            'upline_id' => $user->upline_id,
            'role' => $user->role,
            'id_number' => $user->id_number,
            'status' => $user->status,
            'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
            'group_id' => $user->group->group_id ?? null,
            'group_name' => $user->group->group->name ?? null,
            'group_color' => $user->group->group->color ?? null,
            'upline_name' => $user->upline->full_name ?? null,
            'upline_profile_photo' => $user->upline ? $user->upline->getFirstMediaUrl('profile_photo') : null,
            'total_direct_member' => $user->directChildren->where('role', 'member')->count(),
            'total_direct_ib' => $user->directChildren->where('role', 'ib')->count(),
            // 'kyc_verification' => $user->getMedia('kyc_verification'),
            // 'kyc_approved_at' => $user->kyc_approved_at,
            'kyc_status' => $user->kyc_status,
        ];

        $paymentAccounts = $user->paymentAccounts()
            ->latest()
            ->get();

        return response()->json([
            'userDetail' => $userData,
            'paymentAccounts' => $paymentAccounts
        ]);
    }

    public function deleteMember(Request $request)
    {
        $user = User::find($request->id);

        // // Check for associated trading accounts and users
        // $tradingAccounts = $user->tradingAccounts;
        // $tradingUsers = $user->tradingUsers;

        // // Proceed with cTrader logic only if both trading accounts or trading users are not empty
        // if ($tradingAccounts->isNotEmpty() || $tradingUsers->isNotEmpty()) {
        //     $cTraderService = new CTraderService();

        //     // Check connection status
        //     $conn = $cTraderService->connectionStatus();
        //     if ($conn['code'] != 0) {
        //         return back()->with('toast', [
        //             'title' => 'Connection Error',
        //             'type' => 'error'
        //         ]);
        //     }

        //     // Iterate through trading accounts and users
        //     foreach ($tradingAccounts as $tradingAccount) {
        //         // Get user info from cTrader service
        //         try {
        //             $accData = (new CTraderService())->getUser($tradingAccount->meta_login);

        //             if (empty($accData)) {
        //                 $tradingAccount->trading_user->delete();
        //                 $tradingAccount->delete();
        //             } else {
        //                 // Proceed with updating tradingAccount information
        //                 (new UpdateTradingUser)->execute($tradingAccount->meta_login, $accData);
        //                 (new UpdateTradingAccount)->execute($tradingAccount->meta_login, $accData);
        //             }
        //         } catch (\Throwable $e) {
        //             Log::error($e->getMessage());
        //             return back()->with('toast', [
        //                 'title' => 'No Account Found',
        //                 'type' => 'error'
        //             ]);
        //         }

        //         // Check if the account has a balance or equity
        //         if ($tradingAccount->balance > 0 || $tradingAccount->equity > 0 || $tradingAccount->credit > 0 || $tradingAccount->cash_equity > 0) {
        //             return back()->with('toast', [
        //                 'title' => trans('public.account_have_balance'),
        //                 'type' => 'error'
        //             ]);
        //         }

        //         // Attempt to delete the trading account
        //         try {
        //             $cTraderService->deleteTrader($tradingAccount->meta_login);
        //             $tradingAccount->trading_user->delete();
        //             $tradingAccount->delete();
        //         } catch (\Throwable $e) {
        //             Log::error('Failed to delete trading account: ' . $e->getMessage());
        //             return back()->with('toast', [
        //                 'title' => 'No Account Found',
        //                 'type' => 'error'
        //             ]);
        //         }
        //     }
        // }
        
        // // Get the upline's group ID using the upline relationship
        // $groupId = $user->upline?->group->group_id ?? 1;

        // // If trading accounts or users do not exist, handle user deletion without cTrader logic
        // $relatedUsers = User::where('hierarchyList', 'like', '%-' . $user->id . '-%')->get();

        // foreach ($relatedUsers as $relatedUser) {
        //     $updatedHierarchyList = str_replace('-' . $user->id . '-', '-', $relatedUser->hierarchyList);
        //     $relatedUser->hierarchyList = $updatedHierarchyList;

        //     // Update the upline
        //     $hierarchyArray = array_filter(explode('-', $updatedHierarchyList));
        //     $relatedUser->upline_id = !empty($hierarchyArray) ? end($hierarchyArray) : null;

        //     $relatedUser->assignedGroup($groupId);

        //     $relatedUser->save();
        // }

        // $user->background()->delete();
        // $user->beneficiary()->delete();
        // $user->group()->delete();
        // $user->wallets()->delete();
        // $user->active_subscriptions()->delete();
        // $user->active_trading_accounts()->delete();
        // $user->kycs()->delete();
        $user->delete();

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_delete_member_success'),
            'type' => 'success'
        ]);
    }

    public function access_portal(User $user)
    {
        $dataToHash = $user->name . $user->email . $user->id_number;
        $hashedToken = md5($dataToHash);

        $currentHost = $_SERVER['HTTP_HOST'];

        // Retrieve the app URL and parse its host
        $appUrl = parse_url(config('app.url'), PHP_URL_HOST);
        $memberProductionUrl = config('app.member_production_url');

        if ($currentHost === 'luckystar-admin.currenttech.pro') {
            $url = "https://luckystar-user.currenttech.pro/admin_login/$hashedToken";
        } elseif ($currentHost === $appUrl) {
            $url = "$memberProductionUrl/admin_login/$hashedToken";
        } else {
            return back();
        }

        $params = [
            'admin_id' => Auth::id(),
            'admin_name' => Auth::user()->name,
        ];

        $redirectUrl = $url . "?" . http_build_query($params);
        return Inertia::location($redirectUrl);
    }

}
