<?php

namespace App\Policies;

use App\Models\Student;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any orders.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->hasAnyPermission(['View any Student']);
    }

    /**
     * Determine whether the user can view the student.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Student  $student
     * @return mixed
     */
    public function view(User $user, Student $student)
    {
        return $user->hasAnyPermission(['View any Student']) || $user->id == $student->creater_uid;
    }

    /**
     * Determine whether the user can create students.
     * 必须是没学生角色才可以注册后post.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasAnyPermission(['Create a Student']);
        // //如果登陆用户没有 角色，则可以创建
        // //如果登陆用户是代理，也可以创建一个吧？
        // return ! $user->hasRole('student')
        // || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the student.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Student  $student
     * @return mixed
     */
    public function update(User $user, Student $student)
    {
        return $user->hasAnyPermission(['Update any Student'])
            || ($user->hasAnyPermission(['Update own Student']) && $user->id == $student->creater_uid);
    }

    /**
     * Determine whether the user can delete the student.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Student  $student
     * @return mixed
     */
    public function delete(User $user, Student $student)
    {
        //
    }

    /**
     * Determine whether the user can restore the student.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Student  $student
     * @return mixed
     */
    public function restore(User $user, Student $student)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the student.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Student  $student
     * @return mixed
     */
    public function forceDelete(User $user, Student $student)
    {
        //
    }
}
