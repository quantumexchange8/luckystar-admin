<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupHasAccountType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'group_id',
        'account_type_id',
    ];
}
