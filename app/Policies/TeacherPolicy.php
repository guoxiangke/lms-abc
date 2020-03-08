<?php

namespace App\Policies;

use App\Models\Teacher;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeacherPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the teacher.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Teacher  $teacher
     * @return mixed
     */
    public function view(User $user, Teacher $teacher)
    {
        //
    }

    /**
     * Determine whether the user can create teachers.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return ! $user->hasRole('teacher');
    }

    /**
     * Determine whether the user can update the teacher.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Teacher  $teacher
     * @return mixed
     */
    public function update(User $user, Teacher $teacher)
    {
        //
    }

    /**
     * Determine whether the user can delete the teacher.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Teacher  $teacher
     * @return mixed
     */
    public function delete(User $user, Teacher $teacher)
    {
        //
    }

    /**
     * Determine whether the user can restore the teacher.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Teacher  $teacher
     * @return mixed
     */
    public function restore(User $user, Teacher $teacher)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the teacher.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Teacher  $teacher
     * @return mixed
     */
    public function forceDelete(User $user, Teacher $teacher)
    {
        //
    }
}
