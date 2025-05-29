<?php

namespace App\Http\Controllers;

use App\Models\Kyc;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Inertia\Inertia;
use App\Models\Group;
use App\Models\Wallet;
use App\Models\Country;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
use App\Services\GroupService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\RunningNumberService;
use App\Http\Requests\AddMemberRequest;
use App\Services\TradingAccountService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;
use Carbon\Carbon;

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

            $query = User::with([
                'group.group:id,name,color',
                'country:id,name,translations,iso2',
                'upline:id,first_name,last_name,email,id_number',
                'rank:id,rank_name',
                'media',
                'kycs'
            ])
                ->withSum('active_subscriptions', 'subscription_amount')
                ->whereNot('role', 'super_admin');


            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($query) use ($keyword) {
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

            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            if (!empty($data['filters']['group_id']['value'])) {
                $query->whereHas('group', function($q) use ($data) {
                    $q->where('group_id', $data['filters']['group_id']['value']['id']);
                });
            }

            if (!empty($data['filters']['referrers']['value'])) {
                $selected_referrers = $data['filters']['referrers']['value'];
                $userIds = array_column($selected_referrers, 'user_id');

                $query->whereIn('upline_id', $userIds);
            }

            if (!empty($data['filters']['status']['value'])) {
                $query->where('status', $data['filters']['status']['value']);
            }

            if (!empty($data['filters']['kyc_status']['value'])) {
                $statusFilter = $data['filters']['kyc_status']['value'];
                if ($statusFilter === 'verified') {
                    $query->whereHas('kycs')
                    ->whereDoesntHave('kycs', function ($q) {
                        $q->where('kyc_status', '!=', 'verified');
                    });
                } elseif ($statusFilter === 'unverified') {
                    $query->where(function ($q) {
                        $q->whereDoesntHave('kycs')
                        ->orWhereHas('kycs', function ($q2) {
                            $q2->where('kyc_status', '!=', 'verified');
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

            $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

            $last_month_users = (clone $query)
                ->whereDate('created_at', '<=', $endOfLastMonth)
                ->count();

            $total_users = (clone $query)
                ->count();

            $users_trend = $total_users - $last_month_users;

            $verified_users = (clone $query)
                ->whereHas('kycs', function ($q) {
                    $q->where('kyc_status', 'verified');
                })
                ->count();

            $unverified_users = (clone $query)
                ->where(function ($q) {
                    $q->whereDoesntHave('kycs')
                        ->orWhereHas('kycs', function ($sub) {
                            $sub->where('kyc_status', '!=', 'verified');
                        });
                })
                ->count();

            $users = $query->paginate($data['rows']);
            $users->getCollection()->transform(function ($user) {
                $country = $user->country ?? null;
                $group = $user->group->group ?? null;
                $upline = $user->upline ?? null;
                $rank = $user->rank ?? null;

                $user->active_capital = $user->active_subscriptions_sum_subscription_amount;
                $user->profile_photo = $user?->getFirstMediaUrl('profile_photo');

                $user->country_name = $country?->name;
                $user->country_iso2 = $country?->iso2;
                $user->country_translations = $country?->translations;

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
                unset($user->media);

                return $user;
            });

            return response()->json([
                'success' => true,
                'data' => $users,
                'totalUsers' => (float) $total_users,
                'usersTrend' => (float) $users_trend,
                'verifiedUsers' => (float) $verified_users,
                'unverifiedUsers' => (float) $unverified_users,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function addNewMember(AddMemberRequest $request)
    {
        $upline_id = $request->upline_id;
        $upline = User::find($upline_id);

        if(empty($upline->hierarchyList)) {
            $hierarchyList = "-" . $upline_id . "-";
        } else {
            $hierarchyList = $upline->hierarchyList . $upline_id . "-";
        }

        $dial_code = $request->dial_code;
        $country = Country::find($dial_code['id']);

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
        ]);

        $user->setReferralId();

        $id_no = 'LID' . Str::padLeft($user->id, 6, "0");
        $user->id_number = $id_no;
        $user->save();

        if ($upline->group){
            (new GroupService())->addUserToGroup($upline->group->group_id, $user->id);
            $group_rank_setting = $upline->group->group->group_rank_settings()->first();
            $user->setting_rank_id = $group_rank_setting->id;
        } else {
            (new GroupService())->addUserToGroup(Group::first()->id, $user->id);
        }

        Wallet::create([
            'user_id' => $user->id,
            'type' => 'cash_wallet',
            'address' => "LS-CW-". Str::padLeft($user->id, 7, "0"),
            'currency' => 'USD',
            'currency_symbol' => '$'
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'type' => 'bonus_wallet',
            'address' => "LS-BW-". Str::padLeft($user->id, 7, "0"),
            'currency' => 'USD',
            'currency_symbol' => '$'
        ]);

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
        $memberId = $request->input('id');

        // Fetch the member and get their children (downline) IDs
        $member = User::findOrFail($memberId);
        $excludedIds = $member->getChildrenIds();
        $excludedIds[] = $memberId;

        // Fetch uplines who are not in the excluded list
        $uplines = User::whereNotIn('id', $excludedIds)
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

    public function transferUpline(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'user_id'   => 'required|exists:users,id',
            'upline_id' => 'required|exists:users,id',
        ]);

        // Find the user to be transferred
        $user = User::findOrFail($request->input('user_id'));

        // Check if the new upline is valid and not the same as the current one
        if ($user->upline_id === $request->input('upline_id')) {
            return back()->with('toast', [
                'title' => trans('public.transfer_same_upline_warning'),
                'type'  => 'warning',
            ]);
        }

        // Find the new upline
        $newUpline = User::findOrFail($request->input('upline_id'));

        // Step 1: Update the user's hierarchyList to reflect the new upline's hierarchy and ID
        $user->hierarchyList = $newUpline->hierarchyList . $newUpline->id . '-';
        $user->upline_id = $newUpline->id;

        // Update the user's group relationship
        if ($newUpline->group) {
            (new GroupService())->updateUserGroup($newUpline->group->group_id, $user->id);
        }

        // Save the updated hierarchyList and upline_id for the user
        $user->save();

        // Step 3: Update related users' hierarchyList
        $relatedUsers = User::where('hierarchyList', 'like', '%-' . $user->id . '-%')->get();

        foreach ($relatedUsers as $relatedUser) {
            $userIdSegment = '-' . $user->id . '-';

            // Find the position of `-user_id-` in the related user's hierarchyList
            $pos = strpos($relatedUser->hierarchyList, $userIdSegment);

            if ($pos !== false) {
                // Extract the part after the user's ID segment (tail part)
                $tailHierarchy = substr($relatedUser->hierarchyList, $pos + strlen($userIdSegment));

                // Prepend the user's new hierarchyList + user ID to the tail part
                $relatedUser->hierarchyList = $user->hierarchyList . $user->id . '-' . $tailHierarchy;
            }

            // Save the updated hierarchyList for the related user
            $relatedUser->save();
        }

        // Step 4 update the related user group has user as transfer upline will change group as well
        // Get the group_id from the new upline's group relationship
        $group_id = $newUpline->group->group_id;

        $relatedUserIds = $relatedUsers->pluck('id')->toArray();

        // If the group_id is valid, update related users group
        if ($group_id && !empty($relatedUserIds)) {
            foreach ($relatedUserIds as $relatedUserId) {
                (new GroupService())->updateUserGroup($group_id, $relatedUserId);
            }
        }

        // Return a success response
        return back()->with('toast', [
            'title' => trans('public.toast_transfer_upline_success'),
            'type'  => 'success',
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
            'user' => $user,
            'tradingAccountsCount' => $user->active_trading_accounts()->count(),
        ]);
    }

    public function getUserData(Request $request)
    {
        $user = User::with('country', 'group.group', 'upline', 'rank', 'active_subscriptions')
            ->findOrFail($request->id);

        $capital = $user->active_subscriptions->sum('subscription_amount');

        $userData = [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->full_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'dial_code' => $user->dial_code,
            'phone' => $user->phone,
            'phone_number' => $user->phone_number,
            'country_id' => $user->country->id ?? null,
            'country_iso2' => $user->country->iso2 ?? null,
            'country_name' => $user->country->name ?? null,
            'country_translations' => $user->country->translations ?? null,
            'nationality' => $user->nationality,
            'identity_number' => $user->identity_number,
            'address' => $user->address,
            'upline_name' => $user->upline->full_name ?? null,
            'upline_id_number' => $user->upline->id_number ?? null,
            'upline_email' => $user->upline->email ?? null,
            'upline_profile_photo' => $user->upline ? $user->upline->getFirstMediaUrl('profile_photo') : null,
            'id_number' => $user->id_number,
            'status' => $user->status,
            'gender' => $user->gender,
            'rank_name' => $user->rank->rank_name ?? null,
            'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
            'capital' => $capital,
            'group_name' => $user->group->group->name ?? null,
            'group_color' => $user->group->group->color ?? null,
            'total_direct_member' => $user->directChildren->where('role', 'member')->count(),
            'total_direct_ib' => $user->directChildren->where('role', 'ib')->count(),
            'kyc_status' => $user->kyc_status,
        ];

        $paymentAccounts = $user->paymentAccounts()
            ->latest()
            ->get();

        $kycs = $user->kycs
            ->whereIn('category', ['proof_of_identity', 'proof_of_residency'])
            ->keyBy('category');

        $identity = $kycs->get('proof_of_identity');
        $residency = $kycs->get('proof_of_residency');

        if ($identity) {
            $identity->front_image = $identity->getFirstMediaUrl('front_identity');
            $identity->back_image = $identity->getFirstMediaUrl('back_identity');
            $identity->passport_image = $identity->getFirstMediaUrl('passport_identity');
        }

        if ($residency) {
            $residency->residency_proof = $residency->getFirstMediaUrl('residency_proof');
        }

        return response()->json([
            'userDetail' => $userData,
            'paymentAccounts' => $paymentAccounts,
            'proof_of_identity' => $identity,
            'proof_of_residency' => $residency,
        ]);
    }

    public function getKycDetails(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $kycs = Kyc::where('user_id', $request->user_id)
            ->whereIn('category', ['proof_of_identity', 'proof_of_residency'])
            ->get()
            ->keyBy('category');

        $identity = $kycs->get('proof_of_identity');
        $residency = $kycs->get('proof_of_residency');

        if ($identity) {
            $identity->front_image = $identity->getFirstMediaUrl('front_identity');
            $identity->back_image = $identity->getFirstMediaUrl('back_identity');
            $identity->passport_image = $identity->getFirstMediaUrl('passport_identity');
        }

        if ($residency) {
            $residency->residency_proof = $residency->getFirstMediaUrl('residency_proof');
        }

        return response()->json([
            'proof_of_identity' => $identity,
            'proof_of_residency' => $residency,
        ]);
    }

    public function downloadMedia($mediaId)
    {
        // $media = Media::findOrFail($mediaId);
        // return Storage::disk('s3')->download($media->getPath(), $media->file_name);

        $media = Media::findOrFail($mediaId);
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('s3');

        return $disk->download($media->getPath(), $media->file_name);
    }

    public function updateKycStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:kycs,id',
            'action' => 'required|string|in:approve,reject',
            'remarks' => 'nullable|string',
        ]);

        $action = $request->input('action');
        $kyc = Kyc::findOrFail($request->id);

        if ($request->action === 'approve') {
            $kyc->kyc_status = 'verified';
            $kyc->kyc_approval_at = now();
            $kyc->kyc_approval_description = $request->remarks ?? null;
        } else if ($request->action === 'reject') {
            $kyc->kyc_status = 'unverified';
            $kyc->kyc_approval_at = now();
            $kyc->kyc_approval_description = $request->remarks ?? null;
        }

        $kyc->save();

        return redirect()->back()->with('toast', [
            'title' => $action == 'approve' ? trans('public.toast_kyc_approved') : trans('public.toast_kyc_rejected'),
            'type' => 'success'
        ]);
    }

    public function updateProfileInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($request->user_id)],
            'first_name' => ['required', 'regex:/^[a-zA-Z0-9\p{Han}. ]+$/u', 'max:255'],
            'last_name' => ['required', 'regex:/^[a-zA-Z0-9\p{Han}. ]+$/u', 'max:255'],
            'username' => ['required', 'max:255', Rule::unique('users', 'username')->ignore($request->user_id),],
            'dial_code' => ['required'],
            'phone' => ['required', 'max:255'],
            'phone_number' => ['required', 'max:255', Rule::unique(User::class)->ignore($request->user_id)],
            'country_id' => ['required'],
            'gender' => ['required'],
            'address' => ['required'],
        ])->setAttributeNames([
            'email' => trans('public.email'),
            'first_name' => trans('public.first_name'),
            'last_name' => trans('public.last_name'),
            'username' => trans('public.username'),
            'dial_code' => trans('public.phone_code'),
            'phone' => trans('public.phone'),
            'phone_number' => trans('public.phone_number'),
            'country_id' => trans('public.nationality'),
            'gender' => trans('public.gender'),
            'address' => trans('public.address'),
        ]);
        $validator->validate();

        $user = User::find($request->user_id);
        $country = Country::find($request->country_id);

        // Update user contact information
        $user->update([
            'email' => $request->email,
            'name' => $request->name,
            'dial_code' => $request->dial_code,
            'phone' => $request->phone,
            'phone_number' => $request->phone_number,
            'country_id' => $country->id,
            'nationality' => $country->nationality,
            'gender' => $request->gender,
            'address' => $request->address,
        ]);

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_update_profile_success'),
            'type' => 'success'
        ]);
    }

    public function getFinancialInfoData(Request $request)
    {
        $query = Transaction::query()
            ->where('user_id', $request->id)
            ->where('category', 'trading_account')
            ->where('status', 'success');

        $transactions = $query->whereIn('transaction_type', ['deposit', 'withdrawal'])
            ->latest()
            ->get();

        $transaction_history = [];
        foreach ($transactions as $transaction) {
            $transaction_history[] = [
                'category' => $transaction->category,
                'transaction_type' => $transaction->transaction_type,
                'from_meta_login' => $transaction->from_meta_login,
                'to_meta_login' => $transaction->to_meta_login,
                'amount' => $transaction->amount,
                'transaction_charges' => $transaction->transaction_charges,
                'transaction_amount' => $transaction->transaction_amount,
                'status' => $transaction->status,
                'comment' => $transaction->comment,
                'remarks' => $transaction->remarks,
                'created_at' => $transaction->created_at,
                'approval_at' => $transaction->approval_at,
            ];
        }

        $wallets = Wallet::where('user_id', $request->id)
            ->whereIn('type', ['cash_wallet', 'bonus_wallet'])
            ->get();

        return response()->json([
            'transactionHistory' => $transaction_history,
            'wallets' => $wallets,
        ]);
    }

    public function walletAdjustment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'remarks' => ['nullable'],
        ])->setAttributeNames([
            'action' => trans('public.action'),
            'amount' => trans('public.amount'),
            'remarks' => trans('public.remarks'),
        ]);
        $validator->validate();

        $action = $request->action;
        $amount = $request->amount;
        $wallet = Wallet::findOrFail($request->id);

        // Validate balance for any *_out action
        if (Str::endsWith($action, '_out') && $wallet->balance < $amount) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
        }

        $isOut = Str::endsWith($action, '_out');
        $isIn = Str::endsWith($action, '_in');

        Transaction::create([
            'user_id' => $wallet->user_id,
            'category' => 'wallet',
            'transaction_type' => $action,
            'from_wallet_id' => $isOut ? $wallet->id : null,
            'to_wallet_id' => $isIn ? $wallet->id : null,
            'transaction_number' => RunningNumberService::getID('transaction'),
            'amount' => $amount,
            'transaction_charges' => 0,
            'transaction_amount' => $amount,
            'old_wallet_amount' => $wallet->balance,
            'new_wallet_amount' => $isOut ? $wallet->balance - $amount : $wallet->balance + $amount,
            'status' => 'success',
            'remarks' => $request->remarks,
            'approval_at' => now(),
            'handle_by' => Auth::id(),
        ]);

        $wallet->balance = $isOut ? $wallet->balance - $amount : $wallet->balance + $amount;
        $wallet->save();

        return redirect()->back()->with('toast', [
            'title' => trans('public.wallet_adjustment_success'),
            'type' => 'success',
        ]);
    }

    /**
     * @throws Throwable
     */
    public function getTradingAccounts(Request $request)
    {
        $metaLogins = TradingAccount::query()
            ->where('user_id', $request->id)
            ->pluck('meta_login');

        if (App::environment(['production', 'staging'])) {
            foreach ($metaLogins as $metaLogin) {
                (new TradingAccountService())->getUserInfo((int) $metaLogin);
            }
        }

        $tradingAccounts = TradingAccount::with([
            'user',
            'user.media',
            'account_type',
            'active_subscriptions:meta_login,master_meta_login,approval_at',
            'active_subscriptions.trading_master:meta_login,master_name',
            'trading_master:meta_login',
            'trading_user'
        ])
            ->withSum('active_subscriptions', 'subscription_amount')
            ->where('user_id', $request->id)
            ->get();

        $tradingAccounts->transform(function ($account) {
            $account->has_active_or_pending_subscriptions = $account->has_active_or_pending_subscriptions();
            return $account;
        });

        return response()->json([
            'tradingAccounts' => $tradingAccounts,
        ]);
    }

    public function getAdjustmentHistoryData(Request $request)
    {
        $adjustment_history = Transaction::with('to_wallet:id,type', 'from_wallet:id,type')
            ->where('user_id', $request->id)
            ->whereIn('transaction_type', ['cash_in', 'cash_out', 'bonus_in', 'bonus_out', 'balance_in','balance_out','credit_in','credit_out',])
            ->where('status', 'success')
            ->latest()
            ->get();

        return response()->json($adjustment_history);
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
