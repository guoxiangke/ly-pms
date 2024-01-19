<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can upload files.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function uploadFiles(User $user): bool
    {
        return true;
    }
}
