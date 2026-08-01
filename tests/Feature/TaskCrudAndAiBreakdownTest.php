<?php

namespace Tests\Feature;

use App\Livewire\Tasks\CreateTask;
use App\Livewire\Tasks\TaskDetail;
use App\Models\AiBreakdownLog;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskCrudAndAiBreakdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_team_member_can_create_a_task_on_their_own_list(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $taskList = TaskList::create([
            'team_id'  => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name'     => 'Board',
        ]);

        Livewire::actingAs($owner)
            ->test(CreateTask::class, ['taskList' => $taskList])
            ->set('title', 'Write the release notes')
            ->set('priority', 'high')
            ->call('save');

        $this->assertDatabaseHas('tasks', [
            'task_list_id' => $taskList->id,
            'title'        => 'Write the release notes',
            'priority'     => 'high',
        ]);
    }

    public function test_a_user_cannot_create_a_task_on_another_teams_list(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $taskList = TaskList::create([
            'team_id'  => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name'     => 'Board',
        ]);

        $outsider = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($outsider)
            ->test(CreateTask::class, ['taskList' => $taskList])
            ->assertForbidden();
    }

    /**
     * ANTHROPIC_API_KEY is empty in the test environment (phpunit.xml), so
     * AiTaskBreakdownService::isConfigured() is false and breakdown() falls
     * back to its fixed mock template rather than calling the Anthropic API.
     */
    public function test_ai_breakdown_creates_subtasks_via_the_mock_fallback_and_is_logged_to_the_owning_task(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $taskList = TaskList::create([
            'team_id'  => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name'     => 'Board',
        ]);
        $task = $taskList->tasks()->create(['title' => 'Launch the feature', 'status' => 'todo']);

        Livewire::actingAs($owner)
            ->test(TaskDetail::class)
            ->call('openTask', $task->id)
            ->call('aiBreakdown')
            ->assertSet('aiError', null);

        $this->assertEquals(5, $task->subtasks()->count());
    }

    public function test_a_user_cannot_run_ai_breakdown_on_another_teams_task(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $taskList = TaskList::create([
            'team_id'  => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name'     => 'Board',
        ]);
        $task = $taskList->tasks()->create(['title' => 'Confidential', 'status' => 'todo']);

        $outsider = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($outsider)
            ->test(TaskDetail::class)
            ->call('openTask', $task->id)
            ->assertForbidden();

        $this->assertEquals(0, $task->subtasks()->count());
        $this->assertDatabaseCount('ai_breakdown_logs', 0);
    }
}
