<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupHasUser extends Model
{
    public $timestamps = false;

    protected $primaryKey = null;

    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'user_id',
    ];

    // Relations
    public function group(): belongsTo
    {
        return $this->belongsTo(Group::class, 'group_id', 'id');
    }

    public function user(): belongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
