<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the "Attempt to read property 'id' on null" bug:
 * the auth:sanctum/verified middleware group on these routes only requires
 * an authenticated, verified user — it does not require team membership.
 * A user with no current team (e.g. removed from their last team, or who
 * never completed team setup) previously crashed the dashboard route and
 * the list-creation action because both dereferenced
 * `->currentTeam->id`/`->currentTeam->taskLists()` without a null check.
 */
class DashboardNullTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_with_no_team_is_redirected_to_team_creation_from_dashboard(): void
    {
        $user = User::factory()->create([
            'current_team_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('teams.create'));
    }

    public function test_authenticated_user_with_a_team_sees_the_dashboard(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_authenticated_user_with_no_team_cannot_create_a_task_list(): void
    {
        $user = User::factory()->create([
            'current_team_id' => null,
        ]);

        $this->actingAs($user)
            ->post(route('task-lists.store'), [
                'name' => 'Orphan Board',
            ])
            ->assertForbidden();
    }
}
