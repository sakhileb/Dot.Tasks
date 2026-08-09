# Dot.Tasks: Overdue-Task Escalation + Approval Gate

## Context

Dot.Tasks' autonomy classification audit (`Dot.Brain/platforms/dot-tasks.md`, 2026-08-08) honestly found no real Level 2 process: no queued jobs exist (`app/Jobs/` doesn't exist as a directory, no class implements `ShouldQueue`), the one AI capability (`AiTaskBreakdownService`) is genuine synchronous end-user self-service gated by `$this->authorize('update', $this->task)`, and the only candidate the audit names — auto-escalating a repeatedly-failing routine to Dot.Projects via the `routine.template.escalated` event — has zero implementing code anywhere (`grep -rln "escalat" app/` returns nothing).

Direct inspection found something the audit's own vocabulary obscures: `routine.template.escalated`'s entire premise — task *templates*, *routines*, recurrence, rework-rate tracking — doesn't correspond to anything in the real app either. `Task` (`app/Models/Task.php`) has `title`, `description`, `status`, `priority`, `due_date`, `assignee_id`, `parent_id` (subtasks), `task_list_id` — no recurrence field, no template concept. `TaskList` (`app/Models/TaskList.php`) is a flat container with `name`, `description`, `color`, `owner_id` — no "queue" or "routine" concept. Dot.Tasks is a flat task-and-list manager, not the high-volume recurring-routine execution substrate the audit's Purpose section describes.

Per the design discussion, this spec builds a real, grounded analog instead of the aspirational one: escalation triggered by a task that's significantly overdue and still not done — a state the app already treats as meaningful (`routes/web.php`'s dashboard already computes an `overdue` count the same way: `due_date < today AND status != 'done'`).

## Goal

A task overdue by more than 7 days and still not `done` becomes an escalation candidate (`TaskEscalationProposal`, `status: pending`) via a new daily scheduled command — detection only, no side effect on the task itself. The task list's owner (`TaskList.owner_id` — a real column that exists today but is checked by zero authorization code anywhere, both `TaskPolicy` and `TaskListPolicy` currently enforce only broad team membership) reviews pending proposals on a new `/escalations` screen: approve sets `tasks.escalated_at` and dispatches a new `TaskEscalated` event (the real effect, happening only here); reject (reason required) leaves the task untouched. Because overdue-ness is re-evaluated on every scheduled run, a rejected task that's still overdue gets a fresh proposal on a later run — the same "defer, re-propose" behavior chosen for Dot.Sheet's backup-pruning gate, carried over here since it's the same recurring-reassessment shape.

## Changes

### 1. `tasks.escalated_at` + `task_escalation_proposals` table

New migration adding `escalated_at` (nullable timestamp) to the existing `tasks` table — a non-invasive marker column, not a new `status` enum value, since `status` (`todo`/`in_progress`/`review`/`done`) drives the kanban board UI and adding a fifth value there would have UI implications outside this spec's scope.

New table `task_escalation_proposals`: `id`, `task_id` (FK `tasks`, `cascadeOnDelete`), `status` (string, default `pending`), `reason` (text, nullable — e.g. "Overdue by 9 days"), `reviewed_by` (nullable FK `users.id`, `nullOnDelete`), `reviewed_at` (nullable timestamp), `rejected_reason` (nullable text).

### 2. `tasks:detect-escalation-candidates` scheduled command

New Artisan command, scheduled daily (Level 1, unattended, read-and-propose only — no write to the task itself). For each `Task` where `status != 'done'` and `due_date` is more than 7 days before today: if a `pending` `TaskEscalationProposal` already exists for that task, skip it (idempotent re-runs); otherwise create one, with `reason` describing how many days overdue.

### 3. `TaskPolicy::escalate()` — makes `TaskList.owner_id` real

New ability on the existing `TaskPolicy`: `escalate(User $user, Task $task): bool { return $user->id === $task->taskList->owner_id; }`. Narrower than `update()`'s existing team-wide boundary — matches the same "narrower than the broad update boundary" shape as Dot.Projects' `closeProject` ability.

### 4. `TaskEscalated` event

New event, `Dispatchable`/`SerializesModels`, constructed with the escalated `Task`. Dispatched from exactly one place: the approval action in §5. Like `Dot.Projects`' `ProjectClosed`, this event will have zero real listeners at first — honestly the same shape: it exists so a future cross-platform Dot.Projects integration has something real to hook into, not a claim that such wiring exists today.

### 5. `EscalationQueue` Livewire component + `/escalations` route

New route, `GET /escalations`, inside the existing `auth:sanctum`/`jetstream.auth_session`/`verified` group. New `EscalationQueue` Livewire component lists `pending` proposals scoped to task lists the current user owns (`TaskEscalationProposal::whereHas('task.taskList', fn ($q) => $q->where('owner_id', auth()->id()))->where('status', 'pending')`) — this query scopes what's *shown*, but the real security boundary is per-action: `approve(int $proposalId)` and `confirmReject(int $proposalId)` take a client-suppliable ID, so each independently calls `$this->authorize('escalate', $proposal->task)` before acting.

`approve()` re-fetches the proposal, no-ops unless still `pending`, sets `tasks.escalated_at = now()`, dispatches `TaskEscalated::dispatch($task)`, and sets the proposal to `approved` + `reviewed_by`/`reviewed_at`. `promptReject()` / `cancelReject()` / `confirmReject()` follow the same confirm-then-act pattern used in every gate this session; `confirmReject()` requires a non-empty reason, sets `rejected` + `rejected_reason` + `reviewed_by`/`reviewed_at`, and leaves `tasks.escalated_at` null.

## Testing

- `tasks:detect-escalation-candidates`: a task overdue by 8+ days and not `done` gets exactly one `pending` proposal, with a `reason` mentioning the day count; a task overdue by only 3 days gets none; a `done` task overdue gets none; running the command twice doesn't duplicate a pending proposal for the same task; after a rejection, a later run (task still overdue) produces a fresh `pending` proposal for the same task.
- `EscalationQueue`: the task list's owner can approve (`tasks.escalated_at` set, `TaskEscalated` dispatched, proposal `approved` recorded) and reject with a reason (`escalated_at` stays null, `rejected_reason` recorded); rejecting without a reason is a no-op; a regular team member who is not the list's owner is blocked with a 403 despite having normal team-wide task-editing access via `TaskPolicy::update()`; acting on a proposal that's no longer `pending` is a no-op.

## Explicitly out of scope

- Any real cross-platform integration with Dot.Projects — `TaskEscalated` is dispatched with zero listeners, per §4's honesty note. Wiring a real Dot.Projects hand-off is separate, future, cross-repo work this spec does not take on.
- Inventing task templates, recurrence, or rework-rate tracking to match the audit's aspirational vocabulary — explicitly rejected during the design discussion in favor of grounding this in real, existing fields (`status`, `due_date`).
- Any change to the kanban board's `status` enum or its UI — `escalated_at` is a separate, non-invasive marker column specifically to avoid this.
- Any change to `TaskPolicy::update()`'s or `TaskListPolicy::update()`'s existing team-wide authorization — team members keep full task-editing access; only the new `escalate` ability is owner-restricted.
- A history/audit trail of every escalation proposal and decision across a task's lifetime beyond what `task_escalation_proposals` already records per-row — no additional reporting UI.
- Notifying the list owner that a proposal is awaiting review — they see it inline the next time they visit `/escalations`; this platform already has a real notification pipeline (`TaskDueSoonNotification`, `TaskAssignedNotification`), but wiring a new one for this is out of scope here, matching the same choice made for Dot.Projects' closure gate.
