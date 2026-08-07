<?php

namespace Tests\Feature;

use App\Livewire\Tasks\TaskDetail;
use App\Models\TaskList;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class TaskAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_task_can_be_assigned_to_a_teammate_and_they_are_notified(): void
    {
        Notification::fake();

        $owner = User::factory()->withPersonalTeam()->create();
        $teammate = User::factory()->create();
        $owner->currentTeam->users()->attach($teammate, ['role' => 'editor']);

        $taskList = TaskList::create([
            'team_id' => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name' => 'Board',
        ]);
        $task = $taskList->tasks()->create(['title' => 'Ship the feature', 'status' => 'todo']);

        Livewire::actingAs($owner)
            ->test(TaskDetail::class)
            ->call('openTask', $task->id)
            ->call('assignTask', $teammate->id);

        $this->assertEquals($teammate->id, $task->fresh()->assignee_id);
        Notification::assertSentTo($teammate, TaskAssignedNotification::class);
    }

    /**
     * Server-side check mirroring App\Livewire\Projects\ProjectBoard::assignTask() in
     * Dot.Projects: a tampered client request naming a user who does not belong to the
     * task list's team must be rejected, not silently assigned.
     */
    public function test_a_task_cannot_be_assigned_to_a_user_outside_the_team(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $outsider = User::factory()->withPersonalTeam()->create();

        $taskList = TaskList::create([
            'team_id' => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name' => 'Board',
        ]);
        $task = $taskList->tasks()->create(['title' => 'Ship the feature', 'status' => 'todo']);

        Livewire::actingAs($owner)
            ->test(TaskDetail::class)
            ->call('openTask', $task->id)
            ->call('assignTask', $outsider->id)
            ->assertForbidden();

        $this->assertNull($task->fresh()->assignee_id);
    }
}
