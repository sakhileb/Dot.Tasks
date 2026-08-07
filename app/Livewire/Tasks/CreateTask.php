<?php

namespace App\Livewire\Tasks;

use App\Models\Task;
use App\Models\TaskList;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CreateTask extends Component
{
    public TaskList $taskList;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('required|in:todo,in_progress,review,done')]
    public string $status = 'todo';

    #[Validate('required|in:low,medium,high,urgent')]
    public string $priority = 'medium';

    #[Validate('nullable|date')]
    public string $dueDate = '';

    #[Validate('nullable|integer|min:1')]
    public ?int $estimatedMinutes = null;

    public bool $showForm = false;

    public function mount(TaskList $taskList): void
    {
        $this->authorize('view', $taskList);

        $this->taskList = $taskList;
    }

    #[On('open-create-task')]
    public function open(string $status = 'todo'): void
    {
        $this->status = $status;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $maxOrder = $this->taskList->tasks()
            ->where('status', $this->status)
            ->max('sort_order') ?? -1;

        Task::create([
            'task_list_id' => $this->taskList->id,
            'title' => $this->title,
            'description' => $this->description ?: null,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->dueDate ?: null,
            'estimated_minutes' => $this->estimatedMinutes,
            'sort_order' => $maxOrder + 1,
        ]);

        $this->reset(['title', 'description', 'dueDate', 'estimatedMinutes']);
        $this->showForm = false;
        $this->dispatch('task-created');
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['title', 'description', 'dueDate', 'estimatedMinutes']);
    }

    public function render(): View
    {
        return view('livewire.tasks.create-task');
    }
}
