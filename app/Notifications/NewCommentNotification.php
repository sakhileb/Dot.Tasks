<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;

/**
 * In-app (database channel) notification sent to a task's assignee when
 * someone else comments on it. Dispatched from
 * App\Livewire\Tasks\TaskDetail::addComment().
 */
class NewCommentNotification extends Notification
{
    public function __construct(public Task $task) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $taskList = $this->task->taskList;

        return [
            'type' => 'new_comment',
            'title' => 'New comment on your task',
            'message' => "Someone commented on \"{$this->task->title}\" on \"{$taskList->name}\".",
            'task_list_id' => $taskList->id,
            'task_id' => $this->task->id,
            'url' => route('task-lists.show', $taskList),
        ];
    }
}
