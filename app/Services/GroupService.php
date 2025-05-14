<?php

namespace App\Services;

use App\Models\GroupHasUser;
use DB;

class GroupService
{
    public function addUserToGroup(int $groupId, int $userId): void
    {
        $groupHasUser = new GroupHasUser();
        $groupHasUser->group_id = $groupId;
        $groupHasUser->user_id = $userId;
        $groupHasUser->save();
    }

    public function updateUserGroup(int $newGroupId, int $userId): void
    {
        DB::table('group_has_users')
            ->where('user_id', $userId)
            ->update(['group_id' => $newGroupId]);
    }
}
