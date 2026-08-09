<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a task list owner approves escalating a significantly
 * overdue task (see App\Livewire\Tasks\EscalationQueue::approve()). No
 * listener consumes this yet -- it exists so a future cross-platform
 * Dot.Projects integration has something real to hook into, not because
 * such wiring exists today.
 */
class TaskEscalated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Task $task) {}
}
