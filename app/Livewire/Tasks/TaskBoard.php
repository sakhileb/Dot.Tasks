<?php

namespace App\Livewire\Tasks;

use App\Models\Task;
use App\Models\TaskList;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TaskBoard extends Component
{
    public TaskList $taskList;

    public string $search = '';

    public const COLUMNS = [
        'todo'        => 'To Do',
        'in_progress' => 'In Progress',
        'review'      => 'Review',
        'done'        => 'Done',
    ];

    public function mount(TaskList $taskList): void
    {
        $this->authorize('view', $taskList);

        $this->taskList = $taskList;
    }

    public function updatedSearch(): void
    {
        unset($this->tasksByStatus);
    }

    #[Computed]
    public function tasksByStatus(): array
    {
        $tasks = $this->taskList->tasks()
            ->whereNull('parent_id')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
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
        $this->authorize('update', $this->taskList);

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
