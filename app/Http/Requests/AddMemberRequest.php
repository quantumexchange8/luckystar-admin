<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class AddMemberRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'regex:/^[a-zA-Z0-9\p{Han}. ]+$/u', 'max:255'],
            'last_name' => ['required', 'regex:/^[a-zA-Z0-9\p{Han}. ]+$/u', 'max:255'],
            'username' => ['required', 'regex:/^[a-zA-Z0-9\p{Han}. ]+$/u', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:' . User::class],
            'dial_code' => ['required'],
            'phone' => ['required', 'max:255', 'unique:' . User::class],
            'password' => ['required', Password::defaults(), 'confirmed'],
            'group_id' => ['required'],
            'upline_id' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'first_name' => trans('public.first_name'),
            'last_name' => trans('public.last_name'),
            'username' => trans('public.username'),
            'email' => trans('public.email'),
            'phone' => trans('public.phone_number'),
            'password' => trans('public.password'),
            'group_id' => trans('public.group'),
            'upline_id' => trans('public.upline'),
        ];
    }
}
