<?php

namespace Tests\Feature;

use App\Events\TaskEscalated;
use App\Livewire\Tasks\EscalationQueue;
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
