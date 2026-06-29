<?php

use App\Http\Controllers\Auth\EcosystemAuthController;
use App\Http\Controllers\TaskListController;
use App\Models\Task;
use Illuminate\Support\Facades\Route;

Route::get('/auth/ecosystem', [EcosystemAuthController::class, 'handle'])
    ->name('ecosystem.auth');

Route::get('/', fn () => view('welcome'));

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        $team = auth()->user()->currentTeam;

        $lists = $team
            ->taskLists()
            ->withCount('tasks')
            ->orderBy('sort_order')
            ->get();

        $taskCounts = [
            'todo'        => Task::whereHas('taskList', fn ($q) => $q->where('team_id', $team->id))->where('status', 'todo')->count(),
            'in_progress' => Task::whereHas('taskList', fn ($q) => $q->where('team_id', $team->id))->where('status', 'in_progress')->count(),
            'done'        => Task::whereHas('taskList', fn ($q) => $q->where('team_id', $team->id))->where('status', 'done')->count(),
        ];

        return view('dashboard', compact('lists', 'taskCounts'));
    })->name('dashboard');

    Route::get('/lists/create', fn () => view('task-lists.create'))->name('task-lists.create');
    Route::post('/lists', [TaskListController::class, 'store'])->name('task-lists.store');
    Route::get('/lists/{taskList}', [TaskListController::class, 'show'])->name('task-lists.show');
});
