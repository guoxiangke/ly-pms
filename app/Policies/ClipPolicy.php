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
        return true;
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
