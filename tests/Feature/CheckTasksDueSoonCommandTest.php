<?php

namespace Tests\Feature;

use App\Console\Commands\CheckTasksDueSoon;
use App\Models\TaskList;
use App\Models\User;
use App\Notifications\TaskDueSoonNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckTasksDueSoonCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_notifies_the_assignee_of_a_task_due_within_two_days(): void
    {
        Notification::fake();

        $owner = User::factory()->withPersonalTeam()->create();
        $assignee = User::factory()->create();
        $owner->currentTeam->users()->attach($assignee, ['role' => 'editor']);

        $taskList = TaskList::create([
            'team_id' => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name' => 'Board',
        ]);
        $task = $taskList->tasks()->create([
            'title' => 'Due soon task',
            'status' => 'todo',
            'assignee_id' => $assignee->id,
            'due_date' => now()->addDay(),
        ]);

        $this->artisan(CheckTasksDueSoon::class)->assertSuccessful();

        Notification::assertSentTo($assignee, TaskDueSoonNotification::class, function ($notification) use ($task) {
            return $notification->task->id === $task->id;
        });
    }

    public function test_it_does_not_notify_for_a_task_already_marked_done(): void
    {
        Notification::fake();

        $owner = User::factory()->withPersonalTeam()->create();
        $assignee = User::factory()->create();
        $owner->currentTeam->users()->attach($assignee, ['role' => 'editor']);

        $taskList = TaskList::create([
            'team_id' => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name' => 'Board',
        ]);
        $taskList->tasks()->create([
            'title' => 'Already done',
            'status' => 'done',
            'assignee_id' => $assignee->id,
            'due_date' => now()->addDay(),
        ]);

        $this->artisan(CheckTasksDueSoon::class)->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_it_does_not_send_a_duplicate_notification_within_24_hours(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $assignee = User::factory()->create();
        $owner->currentTeam->users()->attach($assignee, ['role' => 'editor']);

        $taskList = TaskList::create([
            'team_id' => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name' => 'Board',
        ]);
        $task = $taskList->tasks()->create([
            'title' => 'Due soon task',
            'status' => 'todo',
            'assignee_id' => $assignee->id,
            'due_date' => now()->addDay(),
        ]);

        $this->artisan(CheckTasksDueSoon::class)->assertSuccessful();
        $this->assertDatabaseCount('notifications', 1);

        $this->artisan(CheckTasksDueSoon::class)->assertSuccessful();
        $this->assertDatabaseCount('notifications', 1);
    }
}
