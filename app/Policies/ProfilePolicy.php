<?php

namespace App\Policies;

use App\Models\Profile;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the profile.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Profile  $profile
     * @return mixed
     */
    public function view(User $user, Profile $profile)
    {
        return $user->id == $profile->user_id
            || $user->isAdmin();
    }

    /**
     * Determine whether the user can create profiles.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the profile.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Profile  $profile
     * @return mixed
     */
    public function update(User $user, Profile $profile)
    {
        return $user->id === $profile->user_id
            || $user->isAdmin();
    }
}
