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
        'recurrence_type', 'recurrence_interval', 'recurrence_anchor', 'recurrence_parent_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'escalated_at' => 'datetime',
        'recurrence_interval' => 'integer',
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

    public function isRecurring(): bool
    {
        return $this->recurrence_type !== null;
    }

    /** The very first task in this recurring series -- itself, if this task has no parent occurrence. */
    public function recurrenceParent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'recurrence_parent_id');
    }

    /** Every occurrence spawned from this task (only populated on the series' first task). */
    public function recurrenceOccurrences(): HasMany
    {
        return $this->hasMany(Task::class, 'recurrence_parent_id');
    }
}
