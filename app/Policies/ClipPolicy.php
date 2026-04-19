<?php

namespace App\Policies;

use App\Models\Clip;
use App\Models\User;

class ClipPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Clip $clip): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        // 仅允许通过关系入口（LyItem / Album 的 Clips 关系）创建，
        // 屏蔽 /admin/resources/clips 列表页的全局 "+ Create Clip" 按钮。
        $viaResource = request()->query('viaResource') ?? request()->input('viaResource');

        return in_array($viaResource, ['ly-items', 'albums'], true);
    }

    public function update(User $user, Clip $clip): bool
    {
        return true;
    }

    public function delete(User $user, Clip $clip): bool
    {
        return true;
    }

    public function restore(User $user, Clip $clip): bool
    {
        return true;
    }

    public function forceDelete(User $user, Clip $clip): bool
    {
        return false;
    }
}
