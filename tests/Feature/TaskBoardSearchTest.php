<?php

namespace Tests\Feature;

use App\Livewire\Tasks\TaskBoard;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskBoardSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_filters_tasks_by_title(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $taskList = TaskList::create([
            'team_id' => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name' => 'Board',
        ]);
        $taskList->tasks()->create(['title' => 'Write the launch email', 'status' => 'todo']);
        $taskList->tasks()->create(['title' => 'Fix the login bug', 'status' => 'todo']);

        Livewire::actingAs($owner)
            ->test(TaskBoard::class, ['taskList' => $taskList])
            ->assertSee('Write the launch email')
            ->assertSee('Fix the login bug')
            ->set('search', 'launch')
            ->assertSee('Write the launch email')
            ->assertDontSee('Fix the login bug');
    }

    public function test_empty_search_result_shows_a_no_matches_message(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $taskList = TaskList::create([
            'team_id' => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name' => 'Board',
        ]);
        $taskList->tasks()->create(['title' => 'Write the launch email', 'status' => 'todo']);

        Livewire::actingAs($owner)
            ->test(TaskBoard::class, ['taskList' => $taskList])
            ->set('search', 'nonexistent-term-xyz')
            ->assertSee('No tasks match');
    }
}
