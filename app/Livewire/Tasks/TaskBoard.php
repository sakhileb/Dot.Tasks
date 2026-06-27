<?php

namespace App\Livewire\Tasks;

use App\Models\Task;
use App\Models\TaskList;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TaskBoard extends Component
{
    public TaskList $taskList;

    public const COLUMNS = [
        'todo'        => 'To Do',
        'in_progress' => 'In Progress',
        'review'      => 'Review',
        'done'        => 'Done',
    ];

    public function mount(TaskList $taskList): void
    {
        $this->taskList = $taskList;
    }

    #[Computed]
    public function tasksByStatus(): array
    {
        $tasks = $this->taskList->tasks()
            ->whereNull('parent_id')
            ->with(['assignee', 'labels', 'subtasks'])
            ->orderBy('sort_order')
            ->get()
            ->groupBy('status');

        $columns = [];
        foreach (array_keys(self::COLUMNS) as $status) {
            $columns[$status] = $tasks->get($status, collect());
        }

        return $columns;
    }

    public function moveTask(int $taskId, string $newStatus): void
    {
        $task = Task::findOrFail($taskId);
        abort_unless($task->task_list_id === $this->taskList->id, 403);

        $task->update(['status' => $newStatus]);
        unset($this->tasksByStatus);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tasks.task-board');
    }
}
