<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvestmentExport implements FromCollection, WithHeadings
{
    private $query;
    private $status;

    public function __construct($query, $status)
    {
        $this->query = $query;
        $this->status = $status;
    }

    /**
    * @return Collection
    */
    public function collection(): Collection
    {
        $filteredData = $this->query->get();

        $result = array();
        foreach ($filteredData as $data) {
            $result[] = array(
                'created_at' => date_format($data->created_at, 'Y-m-d H:i:s'),
                'user_name' => $data->user->full_name,
                'user_email' => $data->user->email,
                'group' => $data->user?->group->group->name,
                'referer' => $data->user->upline->full_name,
                'type' => trans("public.$data->type"),
                'date' => date_format($data->status == 'revoked' ? $data->terminated_at : $data->approval_at, 'Y-m-d H:i:s'),
                'subscription_number' => $data->subscription_number,
                'meta_login' => $data->meta_login,
                'master_name' => $data->trading_master->master_name,
                'master_meta_login' => $data->master_meta_login,
                'account_type' => $data->trading_master->account_type->name,
                'subscription_amount' => $data->subscription_amount,
                'fund_type' => $data->real_fund > 0 ? trans('public.real_fund') : trans('public.demo_fund'),
                'status' => strtoupper($data->status),
            );
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            trans('public.requested_date'),
            trans('public.name'),
            trans('public.email'),
            trans('public.group'),
            trans('public.referrer'),
            trans('public.type'),
            $this->status == 'revoked' ? trans('public.revoked_date') : trans('public.join_date'),
            trans('public.investment_number'),
            trans('public.account'),
            trans('public.strategy'),
            trans('public.account_number'),
            trans('public.account_type'),
            trans('public.fund_size'),
            trans('public.fund_type'),
            trans('public.status'),
        ];
    }
}
