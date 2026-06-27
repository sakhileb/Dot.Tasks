<?php

namespace App\Livewire\Tasks;

use App\Models\Task;
use App\Models\TaskComment;
use App\Services\AiTaskBreakdownService;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class TaskDetail extends Component
{
    public ?Task $task = null;

    #[Validate('required|string')]
    public string $commentBody = '';

    public bool $breaking = false;
    public ?string $aiError = null;

    #[On('open-task')]
    public function openTask(int $taskId): void
    {
        $this->task      = Task::with(['assignee', 'labels', 'subtasks', 'comments.user'])->findOrFail($taskId);
        $this->aiError   = null;
        $this->breaking  = false;
        $this->commentBody = '';
    }

    public function close(): void
    {
        $this->task = null;
    }

    public function updateStatus(string $status): void
    {
        $this->task?->update(['status' => $status]);
        $this->task = $this->task?->fresh(['assignee', 'labels', 'subtasks', 'comments.user']);
        $this->dispatch('task-updated');
    }

    public function addComment(): void
    {
        $this->validateOnly('commentBody');

        TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => auth()->id(),
            'body'    => $this->commentBody,
        ]);

        $this->commentBody = '';
        $this->task = $this->task->fresh(['assignee', 'labels', 'subtasks', 'comments.user']);
    }

    public function aiBreakdown(): void
    {
        if (! $this->task) {
            return;
        }

        $this->breaking = true;
        $this->aiError  = null;

        $service = app(AiTaskBreakdownService::class);
        $result  = $service->breakdown($this->task, auth()->id());

        if ($result === null) {
            $this->aiError  = 'AI breakdown failed. Please try again.';
            $this->breaking = false;
            return;
        }

        foreach ($result['subtasks'] ?? [] as $i => $sub) {
            Task::create([
                'task_list_id'      => $this->task->task_list_id,
                'parent_id'         => $this->task->id,
                'title'             => $sub['title'],
                'priority'          => $sub['priority'] ?? 'medium',
                'estimated_minutes' => $sub['estimated_minutes'] ?? null,
                'status'            => 'todo',
                'sort_order'        => $i,
            ]);
        }

        $this->breaking = false;
        $this->task     = $this->task->fresh(['assignee', 'labels', 'subtasks', 'comments.user']);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tasks.task-detail');
    }
}
