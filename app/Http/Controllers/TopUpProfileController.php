<?php

namespace App\Http\Controllers;

use App\Models\TopUpProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TopUpProfileController extends Controller
{
    public function index()
    {
        return Inertia::render('TopUpProfile/TopUpProfile');
    }

    public function addTopUpProfile(Request $request)
    {
        $rules = [
            'type' => ['required'],
            'name' => ['required'],
            'platform' => ['required'],
            'payment_app_name' => ['required'],
        ];

        $attributeNames = [
            'type' => trans('public.payment_type'),
            'account_number' => trans('public.account_number'),
            'bank_address' => trans('public.bank_address'),
            'swift_code' => trans('public.swift_code'),
            'country_id' => trans('public.country') . '/' . trans('public.currency'),
            'currency' => trans('public.country') . '/' . trans('public.currency'),
            'payment_url' => trans('public.payment_url'),
            'platform' => trans('public.platform'),
            'secret_key' => trans('public.secret_key'),
            'secondary_key' => trans('public.secondary_key'),
        ];

        $type = $request->type;

        if ($type == 'bank_transfer') {
            $rules['account_number'] = ['required'];
            $rules['bank_address'] = ['required'];
            $rules['swift_code'] = ['required'];
            $rules['country_id'] = ['required'];
            $rules['currency'] = ['required'];

            $attributeNames['name'] = trans('public.bank_name');
            $attributeNames['payment_app_name'] = trans('public.account_name');
        } elseif ($type == 'crypto_payment') {
            $rules['payment_url'] = ['required'];
            $rules['secret_key'] = ['required'];
            $rules['secondary_key'] = ['required'];

            $attributeNames['name'] = trans('public.payment_name');
            $attributeNames['payment_app_name'] = trans('public.payment_app');
        }

        Validator::make($request->all(), $rules)
            ->setAttributeNames($attributeNames)
            ->validate();

        TopUpProfile::create([
            'payment_name' => $request->name,
            'type' => $type,
            'payment_app_name' => $request->payment_app_name,
            'payment_url' => $request->payment_url,
            'secret_key' => $request->secret_key,
            'secondary_key' => $request->secondary_key,
            'account_number' => $request->account_number,
            'bank_address' => $request->bank_address,
            'swift_code' => $request->swift_code,
            'country_id' => $request->country_id,
            'currency' => $type == 'bank_transfer' ? $request->currency : 'USD',
        ]);

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_add_top_up_profile_success"),
            'type' => 'success',
        ]);
    }
}
