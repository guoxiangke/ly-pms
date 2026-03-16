<?php

namespace App\Policies;

use App\Models\Album;
use App\Models\User;

class AlbumPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Album $album): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Album $album): bool
    {
        return true;
    }

    public function delete(User $user, Album $album): bool
    {
        return true;
    }

    public function restore(User $user, Album $album): bool
    {
        return true;
    }

    public function forceDelete(User $user, Album $album): bool
    {
        return false;
    }
}
