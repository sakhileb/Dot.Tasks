<?php

namespace App\Policies;

use App\Models\TaskList;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskListPolicy
{
    use HandlesAuthorization;

    public function view(User $user, TaskList $taskList): bool
    {
        return $user->belongsToTeam($taskList->team);
    }

    public function update(User $user, TaskList $taskList): bool
    {
        return $user->belongsToTeam($taskList->team);
    }
}
