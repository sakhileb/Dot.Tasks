<?php

namespace Tests\Feature;

use App\Livewire\NotificationBell;
use App\Models\TaskList;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_notification_bell_for_authenticated_user(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeLivewire('notification-bell');
    }

    public function test_unread_count_reflects_database_notifications(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $taskList = TaskList::create([
            'team_id' => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name' => 'Board',
        ]);
        $task = $taskList->tasks()->create(['title' => 'Task', 'status' => 'todo']);

        $owner->notify(new TaskAssignedNotification($task));

        $this->assertDatabaseCount('notifications', 1);

        Livewire::actingAs($owner)
            ->test(NotificationBell::class)
            ->assertSet('open', false)
            ->call('toggle')
            ->assertSet('open', true)
            ->assertSee('Task assigned to you');

        $this->assertEquals(1, $owner->fresh()->unreadNotifications()->count());
    }

    public function test_mark_all_as_read_clears_unread_count(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $taskList = TaskList::create([
            'team_id' => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name' => 'Board',
        ]);
        $task = $taskList->tasks()->create(['title' => 'Task', 'status' => 'todo']);

        $owner->notify(new TaskAssignedNotification($task));

        Livewire::actingAs($owner)
            ->test(NotificationBell::class)
            ->call('markAllAsRead');

        $this->assertEquals(0, $owner->fresh()->unreadNotifications()->count());
    }

    public function test_notifications_index_page_lists_notifications(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $taskList = TaskList::create([
            'team_id' => $owner->currentTeam->id,
            'owner_id' => $owner->id,
            'name' => 'Board',
        ]);
        $task = $taskList->tasks()->create(['title' => 'Task', 'status' => 'todo']);

        $owner->notify(new TaskAssignedNotification($task));

        $this->actingAs($owner)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Task assigned to you');
    }
}
