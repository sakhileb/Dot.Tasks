<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'task_list_id', 'assignee_id', 'parent_id',
        'title', 'description', 'status', 'priority', 'due_date',
        'estimated_minutes', 'sort_order', 'escalated_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'escalated_at' => 'datetime',
    ];

    public function taskList(): BelongsTo
    {
        return $this->belongsTo(TaskList::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('sort_order');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'task_label');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    public function aiBreakdownLogs(): HasMany
    {
        return $this->hasMany(AiBreakdownLog::class);
    }
}
