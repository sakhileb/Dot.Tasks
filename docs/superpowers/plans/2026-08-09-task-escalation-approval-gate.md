# Overdue-Task Escalation + Approval Gate Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A task overdue by more than 7 days and still not `done` becomes an escalation candidate (`TaskEscalationProposal`, `status: pending`) via a new daily scheduled command. The task list's owner reviews it: approve sets `tasks.escalated_at` and dispatches `TaskEscalated` (the real effect, happening only here); reject (reason required) leaves the task untouched, and it's re-proposed on a later run if still overdue.

**Architecture:** New `tasks.escalated_at` marker column + `task_escalation_proposals` table. A new scheduled command detects candidates and proposes (never mutates the task). `TaskPolicy::escalate()` narrows authorization to `TaskList.owner_id` — a real, currently-unenforced column. A new `EscalationQueue` Livewire component + `/escalations` route provide the review surface, following this repo's own established `$this->authorize(...)` + `assertForbidden()` convention.

**Tech Stack:** Laravel 13 (`routes/console.php` scheduling), Livewire 3 (`livewire/livewire` ^3.6.4), PHPUnit.

## Global Constraints

- The overdue threshold is exactly 7 days (`due_date` more than 7 days before today) and the task's `status` must not be `done` — copied verbatim from the spec.
- `escalated_at` is a new nullable timestamp column on the existing `tasks` table, not a new `status` enum value — `status` (`todo`/`in_progress`/`review`/`done`) drives the kanban board UI and must not gain a fifth value as part of this work.
- Reviewer authority is `TaskList.owner_id` via a new `TaskPolicy::escalate()` ability — narrower than `update()`'s existing team-wide boundary. No new flag, no new role.
- `TaskEscalated` is dispatched from exactly one place (`EscalationQueue::approve()`) and has zero listeners by design — it exists for a future cross-platform integration, not a claim one exists today.
- A `pending` `TaskEscalationProposal` is never duplicated for the same task on a re-run; a `rejected` proposal does not block a fresh one being created for that same task on a later run if it's still overdue — each run's outcome is its own row, matching the "defer, re-propose" behavior chosen for Dot.Sheet's backup-pruning gate.
- Authorization in the new Livewire component follows this repo's own established convention exactly: `$this->authorize('ability', $model)` (Laravel's Gate-based check), tested with plain `Livewire::test(...)->assertForbidden()` — no `withoutExceptionHandling()`/`expectException()` dance, confirmed against this repo's own `tests/Feature/TaskAuthorizationTest.php`.
- This repo's own `CLAUDE.md` states: "Do not create verification scripts or tinker when tests cover that functionality and prove they work." Task 3 relies on the automated test suite only.
- Every `git add` lists files explicitly, never `-A`/`.` — this repo had pre-existing unrelated uncommitted changes (`application-mark.blade.php`, `layouts/app.blade.php`, `task-lists/show.blade.php`, mark images) stashed before this work started.

---

### Task 1: `escalated_at` + `TaskEscalationProposal` + detection command

**Files:**
- Create: `database/migrations/2026_08_09_000001_add_escalated_at_to_tasks_table.php`
- Create: `database/migrations/2026_08_09_000002_create_task_escalation_proposals_table.php`
- Create: `app/Models/TaskEscalationProposal.php`
- Create: `app/Events/TaskEscalated.php`
- Modify: `app/Policies/TaskPolicy.php`
- Create: `app/Console/Commands/DetectEscalationCandidates.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/DetectEscalationCandidatesCommandTest.php`

**Interfaces:**
- Produces: `tasks.escalated_at` (nullable timestamp), `TaskEscalationProposal::create(array $attributes)` with fillable `task_id`, `status`, `reason`, `reviewed_by`, `reviewed_at`, `rejected_reason`; `TaskEscalationProposal::task(): BelongsTo`; `TaskEscalated::__construct(public Task $task)`; `TaskPolicy::escalate(User $user, Task $task): bool`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/DetectEscalationCandidatesCommandTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Console\Commands\DetectEscalationCandidates;
use App\Models\TaskEscalationProposal;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetectEscalationCandidatesCommandTest extends TestCase
{
    use RefreshDatabase;

    private function taskList(User $owner): TaskList
    {
        return TaskList::create([
            'team_id' => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name' => 'Board',
        ]);
    }

    public function test_a_task_overdue_by_more_than_seven_days_and_not_done_gets_a_pending_proposal(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $taskList = $this->taskList($owner);
        $task = $taskList->tasks()->create([
            'title' => 'Very overdue',
            'status' => 'todo',
            'due_date' => now()->subDays(8),
        ]);

        $this->artisan(DetectEscalationCandidates::class)->assertSuccessful();

        $this->assertDatabaseCount('task_escalation_proposals', 1);
        $this->assertDatabaseHas('task_escalation_proposals', [
            'task_id' => $task->id,
            'status' => 'pending',
        ]);
    }

    public function test_a_task_overdue_by_only_three_days_gets_no_proposal(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $taskList = $this->taskList($owner);
        $taskList->tasks()->create([
            'title' => 'Slightly overdue',
            'status' => 'todo',
            'due_date' => now()->subDays(3),
        ]);

        $this->artisan(DetectEscalationCandidates::class)->assertSuccessful();

        $this->assertDatabaseCount('task_escalation_proposals', 0);
    }

    public function test_a_done_task_overdue_gets_no_proposal(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $taskList = $this->taskList($owner);
        $taskList->tasks()->create([
            'title' => 'Finished late',
            'status' => 'done',
            'due_date' => now()->subDays(10),
        ]);

        $this->artisan(DetectEscalationCandidates::class)->assertSuccessful();

        $this->assertDatabaseCount('task_escalation_proposals', 0);
    }

    public function test_running_the_command_twice_does_not_duplicate_a_pending_proposal(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $taskList = $this->taskList($owner);
        $taskList->tasks()->create([
            'title' => 'Very overdue',
            'status' => 'todo',
            'due_date' => now()->subDays(8),
        ]);

        $this->artisan(DetectEscalationCandidates::class);
        $this->artisan(DetectEscalationCandidates::class);

        $this->assertDatabaseCount('task_escalation_proposals', 1);
    }

    public function test_a_rejected_proposal_gets_a_fresh_one_on_the_next_eligible_run(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $taskList = $this->taskList($owner);
        $task = $taskList->tasks()->create([
            'title' => 'Very overdue',
            'status' => 'todo',
            'due_date' => now()->subDays(8),
        ]);

        $this->artisan(DetectEscalationCandidates::class);

        $existing = TaskEscalationProposal::where('task_id', $task->id)->firstOrFail();
        $existing->update(['status' => 'rejected', 'rejected_reason' => 'Not yet.']);

        $this->artisan(DetectEscalationCandidates::class);

        $this->assertDatabaseCount('task_escalation_proposals', 2);
        $this->assertSame(1, TaskEscalationProposal::where('task_id', $task->id)->where('status', 'pending')->count());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/DetectEscalationCandidatesCommandTest.php`
Expected: FAIL — `App\Console\Commands\DetectEscalationCandidates` doesn't exist, `task_escalation_proposals` table doesn't exist.

- [ ] **Step 3: Write the `escalated_at` migration**

Create `database/migrations/2026_08_09_000001_add_escalated_at_to_tasks_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('escalated_at')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('escalated_at');
        });
    }
};
```

- [ ] **Step 4: Write the `task_escalation_proposals` migration**

Create `database/migrations/2026_08_09_000002_create_task_escalation_proposals_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_escalation_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();
            $table->index(['status', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_escalation_proposals');
    }
};
```

- [ ] **Step 5: Create the `TaskEscalationProposal` model**

Create `app/Models/TaskEscalationProposal.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskEscalationProposal extends Model
{
    protected $fillable = [
        'task_id', 'status', 'reason', 'reviewed_by', 'reviewed_at', 'rejected_reason',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
```

- [ ] **Step 6: Create the `TaskEscalated` event**

Create `app/Events/TaskEscalated.php`:

```php
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
```

- [ ] **Step 7: Add `TaskPolicy::escalate()`**

In `app/Policies/TaskPolicy.php`, add after the existing `update()` method:

```php
    /**
     * Determine whether the user can review an escalation proposal for the
     * task (approve or reject). Narrower than update() -- any team member
     * can edit a task, but only the task list's owner may decide whether an
     * overdue task actually gets escalated.
     */
    public function escalate(User $user, Task $task): bool
    {
        return $user->id === $task->taskList->owner_id;
    }
```

- [ ] **Step 8: Create the `DetectEscalationCandidates` command**

Create `app/Console/Commands/DetectEscalationCandidates.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskEscalationProposal;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Detects tasks that are significantly overdue (more than 7 days past their
 * due date) and still not done, and proposes escalating them -- creating a
 * TaskEscalationProposal for the task list's owner to review. Read-and-propose
 * only: never touches the task itself. Intended to run daily (see
 * routes/console.php). Skips a task that already has an open pending
 * proposal, so re-running the command (or a missed schedule catching up)
 * doesn't create duplicates.
 */
class DetectEscalationCandidates extends Command
{
    protected $signature = 'tasks:detect-escalation-candidates';

    protected $description = 'Propose escalating tasks that are significantly overdue and not done.';

    private const OVERDUE_THRESHOLD_DAYS = 7;

    public function handle(): int
    {
        $cutoff = Carbon::today()->subDays(self::OVERDUE_THRESHOLD_DAYS)->toDateString();

        $tasks = Task::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $cutoff)
            ->where('status', '!=', 'done')
            ->get();

        $proposed = 0;

        foreach ($tasks as $task) {
            $hasPendingProposal = TaskEscalationProposal::where('task_id', $task->id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingProposal) {
                continue;
            }

            $daysOverdue = $task->due_date->diffInDays(Carbon::today());

            TaskEscalationProposal::create([
                'task_id' => $task->id,
                'status' => 'pending',
                'reason' => "Overdue by {$daysOverdue} days.",
            ]);

            $proposed++;
        }

        $this->info("Proposed {$proposed} task escalation(s).");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 9: Schedule the command**

In `routes/console.php`, add a line after the existing `Schedule::command('tasks:check-due-soon')->dailyAt('07:00');`:

```php
Schedule::command('tasks:detect-escalation-candidates')->dailyAt('07:30');
```

- [ ] **Step 10: Run migrations**

Run: `php artisan migrate`
Expected: both new migrations run with no errors.

- [ ] **Step 11: Run tests to verify they pass**

Run: `php artisan test tests/Feature/DetectEscalationCandidatesCommandTest.php`
Expected: PASS (5 tests)

- [ ] **Step 12: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: passes; re-run Step 11 if it reformats anything.

- [ ] **Step 13: Commit**

```bash
git add database/migrations/2026_08_09_000001_add_escalated_at_to_tasks_table.php \
  database/migrations/2026_08_09_000002_create_task_escalation_proposals_table.php \
  app/Models/TaskEscalationProposal.php app/Events/TaskEscalated.php \
  app/Policies/TaskPolicy.php app/Console/Commands/DetectEscalationCandidates.php \
  routes/console.php tests/Feature/DetectEscalationCandidatesCommandTest.php \
  docs/superpowers/plans/2026-08-09-task-escalation-approval-gate.md
git commit -m "$(cat <<'EOF'
feat: detect significantly-overdue tasks as escalation candidates

New tasks:detect-escalation-candidates command, scheduled daily,
proposes escalating a task overdue by more than 7 days and still not
done -- creates a TaskEscalationProposal, never mutates the task
itself. Skips a task that already has an open pending proposal so
re-runs don't duplicate.

New TaskPolicy::escalate() reuses TaskList.owner_id -- a real column
that exists today but was checked by zero authorization code; both
TaskPolicy::update() and TaskListPolicy::update() only enforce broad
team membership.

TaskEscalated event has zero listeners by design (documented in its
own docblock) -- same honest shape as Dot.Projects' ProjectClosed.

A rejected proposal is not remembered as permanent -- since overdue-
ness is re-evaluated every scheduled run, a still-overdue rejected
task gets a fresh proposal next time, confirmed by
test_a_rejected_proposal_gets_a_fresh_one_on_the_next_eligible_run.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 2: `EscalationQueue` review UI

**Files:**
- Create: `app/Livewire/Tasks/EscalationQueue.php`
- Create: `resources/views/livewire/tasks/escalation-queue.blade.php`
- Create: `resources/views/escalations/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/EscalationQueueTest.php`

**Interfaces:**
- Consumes: `TaskEscalationProposal` (Task 1), `TaskEscalated` (Task 1), `TaskPolicy::escalate()` (Task 1).
- Produces: `EscalationQueue::approve(int $proposalId)`, `promptReject(int $proposalId)`, `cancelReject()`, `confirmReject(int $proposalId)`, `$rejectingProposalId`/`$rejectReason` component state, `$this->pendingProposals` computed property.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/EscalationQueueTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Events\TaskEscalated;
use App\Livewire\Tasks\EscalationQueue;
use App\Models\Task;
use App\Models\TaskEscalationProposal;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class EscalationQueueTest extends TestCase
{
    use RefreshDatabase;

    private function pendingProposal(User $owner): array
    {
        $taskList = TaskList::create([
            'team_id' => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name' => 'Board',
        ]);
        $task = $taskList->tasks()->create([
            'title' => 'Very overdue',
            'status' => 'todo',
            'due_date' => now()->subDays(8),
        ]);
        $proposal = TaskEscalationProposal::create([
            'task_id' => $task->id,
            'status' => 'pending',
            'reason' => 'Overdue by 8 days.',
        ]);

        return [$task, $proposal];
    }

    public function test_owner_can_approve_and_the_task_is_escalated(): void
    {
        Event::fake();
        $owner = User::factory()->withPersonalTeam()->create();
        [$task, $proposal] = $this->pendingProposal($owner);

        Livewire::actingAs($owner)
            ->test(EscalationQueue::class)
            ->call('approve', $proposal->id);

        $freshTask = $task->fresh();
        $this->assertNotNull($freshTask->escalated_at);
        $this->assertSame('approved', $proposal->fresh()->status);
        $this->assertSame($owner->id, $proposal->fresh()->reviewed_by);
        Event::assertDispatched(TaskEscalated::class, fn ($event) => $event->task->is($freshTask));
    }

    public function test_owner_can_reject_with_a_reason(): void
    {
        Event::fake();
        $owner = User::factory()->withPersonalTeam()->create();
        [$task, $proposal] = $this->pendingProposal($owner);

        Livewire::actingAs($owner)
            ->test(EscalationQueue::class)
            ->call('promptReject', $proposal->id)
            ->set('rejectReason', 'Blocked on another team, not neglect.')
            ->call('confirmReject', $proposal->id);

        $this->assertSame('rejected', $proposal->fresh()->status);
        $this->assertSame('Blocked on another team, not neglect.', $proposal->fresh()->rejected_reason);
        $this->assertNull($task->fresh()->escalated_at);
        Event::assertNotDispatched(TaskEscalated::class);
    }

    public function test_rejecting_without_a_reason_is_a_noop(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        [, $proposal] = $this->pendingProposal($owner);

        Livewire::actingAs($owner)
            ->test(EscalationQueue::class)
            ->call('promptReject', $proposal->id)
            ->set('rejectReason', '')
            ->call('confirmReject', $proposal->id);

        $this->assertSame('pending', $proposal->fresh()->status);
    }

    public function test_acting_on_a_proposal_no_longer_pending_is_a_noop(): void
    {
        Event::fake();
        $owner = User::factory()->withPersonalTeam()->create();
        [$task, $proposal] = $this->pendingProposal($owner);
        $proposal->update(['status' => 'approved']); // already resolved by someone else

        Livewire::actingAs($owner)
            ->test(EscalationQueue::class)
            ->call('approve', $proposal->id);

        $this->assertNull($task->fresh()->escalated_at, 'a no-op approve must not escalate anything');
        Event::assertNotDispatched(TaskEscalated::class);
    }

    public function test_a_regular_team_member_who_does_not_own_the_list_is_blocked(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        [, $proposal] = $this->pendingProposal($owner);

        $member = User::factory()->create();
        $owner->currentTeam->users()->attach($member, ['role' => 'editor']);

        Livewire::actingAs($member)
            ->test(EscalationQueue::class)
            ->call('approve', $proposal->id)
            ->assertForbidden();
    }

    public function test_escalations_route_requires_authentication(): void
    {
        $this->get('/escalations')->assertRedirect('/login');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/EscalationQueueTest.php`
Expected: FAIL — `App\Livewire\Tasks\EscalationQueue` doesn't exist yet, `/escalations` route doesn't exist yet.

- [ ] **Step 3: Create the `EscalationQueue` Livewire component**

Create `app/Livewire/Tasks/EscalationQueue.php`:

```php
<?php

namespace App\Livewire\Tasks;

use App\Events\TaskEscalated;
use App\Models\TaskEscalationProposal;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class EscalationQueue extends Component
{
    public ?int $rejectingProposalId = null;

    public string $rejectReason = '';

    #[Computed]
    public function pendingProposals(): Collection
    {
        return TaskEscalationProposal::where('status', 'pending')
            ->whereHas('task.taskList', fn ($query) => $query->where('owner_id', auth()->id()))
            ->with('task.taskList')
            ->latest()
            ->get();
    }

    public function approve(int $proposalId): void
    {
        $proposal = TaskEscalationProposal::find($proposalId);

        if (! $proposal) {
            return;
        }

        $this->authorize('escalate', $proposal->task);

        if ($proposal->status !== 'pending') {
            return;
        }

        $proposal->task->update(['escalated_at' => now()]);
        TaskEscalated::dispatch($proposal->task);

        $proposal->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        unset($this->pendingProposals);
    }

    public function promptReject(int $proposalId): void
    {
        $proposal = TaskEscalationProposal::find($proposalId);

        if (! $proposal) {
            return;
        }

        $this->authorize('escalate', $proposal->task);

        $this->rejectingProposalId = $proposalId;
        $this->rejectReason = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingProposalId = null;
        $this->rejectReason = '';
    }

    public function confirmReject(int $proposalId): void
    {
        $proposal = TaskEscalationProposal::find($proposalId);

        if (! $proposal) {
            return;
        }

        $this->authorize('escalate', $proposal->task);

        if ($proposal->status !== 'pending') {
            return;
        }

        if (trim($this->rejectReason) === '') {
            return;
        }

        $proposal->update([
            'status' => 'rejected',
            'rejected_reason' => $this->rejectReason,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $this->rejectingProposalId = null;
        $this->rejectReason = '';
        unset($this->pendingProposals);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tasks.escalation-queue');
    }
}
```

Note: authorization always runs immediately after the proposal is fetched (and before any pending-status no-op check), matching this repo's own `TaskDetail`/`TaskBoard` convention of authorizing as soon as the target model is known — a tampered `proposalId` belonging to someone else's list still gets a real 403, not a silent no-op that happens to look the same.

- [ ] **Step 4: Create the component view**

Create `resources/views/livewire/tasks/escalation-queue.blade.php`:

```blade
<div>
    @if($this->pendingProposals->isEmpty())
        <div class="text-center py-16 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
            <span class="material-symbols-rounded text-gray-400 dark:text-gray-600" style="font-size:32px;">task_alt</span>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No tasks awaiting escalation review.</p>
        </div>
    @endif

    @foreach($this->pendingProposals as $proposal)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 mb-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $proposal->task->title }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        {{ $proposal->task->taskList->name }} &middot; {{ $proposal->reason }}
                    </p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400">
                    Escalation candidate
                </span>
            </div>

            @if($rejectingProposalId === $proposal->id)
                <div class="flex items-center gap-2 mt-3">
                    <input type="text" wire:model="rejectReason" placeholder="Reason for keeping as-is"
                        class="flex-1 border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    <button wire:click="confirmReject({{ $proposal->id }})" class="text-xs px-3 py-1.5 rounded bg-red-600 hover:bg-red-700 text-white font-medium">
                        Confirm Reject
                    </button>
                    <button wire:click="cancelReject" class="text-xs px-3 py-1.5 rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium">
                        Cancel
                    </button>
                </div>
            @else
                <div class="flex items-center gap-2 mt-3">
                    <button wire:click="approve({{ $proposal->id }})" class="text-xs px-3 py-1.5 rounded bg-green-600 hover:bg-green-700 text-white font-medium">
                        Approve Escalation
                    </button>
                    <button wire:click="promptReject({{ $proposal->id }})" class="text-xs px-3 py-1.5 rounded bg-red-600 hover:bg-red-700 text-white font-medium">
                        Reject
                    </button>
                </div>
            @endif
        </div>
    @endforeach
</div>
```

- [ ] **Step 5: Create the page view**

Create `resources/views/escalations/index.blade.php`:

```blade
<x-app-layout>
    <div style="padding:2rem 2.5rem;">

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;">
            <div>
                <h1 style="font-family:'Manrope',sans-serif;font-size:1.5rem;font-weight:700;margin:0 0 0.2rem;">Escalation Queue</h1>
                <p style="font-size:0.8rem;color:#8d90a2;margin:0;">Tasks significantly overdue, awaiting your review</p>
            </div>
        </div>

        <livewire:tasks.escalation-queue />

    </div>
</x-app-layout>
```

- [ ] **Step 6: Add the route**

In `routes/web.php`, add inside the existing `auth:sanctum` / `jetstream.auth_session` / `verified` group, after the `/notifications` route:

```php
    Route::get('/escalations', fn () => view('escalations.index'))->name('escalations.index');
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test tests/Feature/EscalationQueueTest.php`
Expected: PASS (6 tests)

- [ ] **Step 8: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: passes; re-run Step 7 if it reformats anything.

- [ ] **Step 9: Commit**

```bash
git add app/Livewire/Tasks/EscalationQueue.php resources/views/livewire/tasks/escalation-queue.blade.php \
  resources/views/escalations/index.blade.php routes/web.php tests/Feature/EscalationQueueTest.php
git commit -m "$(cat <<'EOF'
feat: escalation review queue for significantly-overdue tasks

New /escalations route + EscalationQueue Livewire component: lists
every pending TaskEscalationProposal for task lists the current user
owns, and lets them approve (the only place tasks.escalated_at is set
and TaskEscalated fires) or reject with a reason (task untouched;
re-proposed on a later run if still overdue).

TaskPolicy::escalate() is checked on every action, immediately after
the target proposal's task is known and before any pending-status
no-op check -- a tampered proposal ID belonging to another owner's
list still gets a real 403, matching this repo's own TaskDetail/
TaskBoard authorization-ordering convention.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 3: Full regression

**Files:** none new — verification only.

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: 0 failures — confirms Tasks 1-2 didn't break `TaskAuthorizationTest`, `CheckTasksDueSoonCommandTest`, `TaskCrudAndAiBreakdownTest`, or anything else.

- [ ] **Step 2: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: passes; re-run Step 1 if it reformats anything.

- [ ] **Step 3: Report completion**

No manual tinker verification for this task -- this repo's own `CLAUDE.md` states "Do not create verification scripts or tinker when tests cover that functionality and prove they work," and Tasks 1-2's tests already exercise the real end-to-end lifecycle: a real overdue task detected by the real `tasks:detect-escalation-candidates` command, reviewed and approved via the real `EscalationQueue` Livewire component, with `escalated_at` actually set and `TaskEscalated` actually dispatched (`DetectEscalationCandidatesCommandTest`, `EscalationQueueTest`). No commit for this task — it's verification only. If Step 1 finds any failures, stop and fix them (return to the relevant earlier task) before considering this plan complete.

## Self-Review Notes

- **Spec coverage:** Task 1 covers spec §1-§4 (`escalated_at`/`task_escalation_proposals`, the detection command, `TaskPolicy::escalate()`, `TaskEscalated`). Task 2 covers spec §5 (`EscalationQueue` + route). Task 3 covers the spec's implicit "this all actually works together" requirement, adapted to this repo's own no-tinker rule.
- **Placeholder scan:** none — every step has literal file content, including full migration/model/event/policy/command/Livewire/view contents.
- **Type consistency:** `TaskEscalationProposal::create()`'s fillable keys, `TaskEscalated::__construct(public Task $task)`, `TaskPolicy::escalate(User $user, Task $task): bool`, and `EscalationQueue`'s `approve`/`promptReject`/`cancelReject`/`confirmReject` signatures and `$rejectingProposalId`/`$rejectReason` properties are used identically everywhere they're referenced across both tasks.
- **Local authorization-testing convention followed, not imported from elsewhere:** this repo's own `TaskAuthorizationTest.php` uses plain `Livewire::test(...)->assertForbidden()` with no `withoutExceptionHandling()`/`expectException()` — Task 2's test matches that exactly, a deliberate choice over the pattern used in other platforms this session (Dot.Projects/Dot.Pulse/Dot.Sheet), documented in the Global Constraints section.
- **"Reject = defer, re-propose" tested across two runs, not assumed:** Task 1's `test_a_rejected_proposal_gets_a_fresh_one_on_the_next_eligible_run` explicitly simulates two command runs with a rejection in between, carrying forward the same test shape used for Dot.Sheet's analogous recurring-reassessment gate.
- **No manual tinker step, per this repo's own rule:** Task 3 explains why it skips the manual-verification step, matching the same reasoning already established for Dot.Projects and Dot.Sheet.
