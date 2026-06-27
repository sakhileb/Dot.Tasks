<?php

use App\Http\Controllers\Auth\EcosystemAuthController;
use App\Http\Controllers\TaskListController;
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
        $lists = auth()->user()->currentTeam
            ->taskLists()
            ->withCount('tasks')
            ->orderBy('sort_order')
            ->get();

        return view('dashboard', compact('lists'));
    })->name('dashboard');

    Route::get('/lists/create', fn () => view('task-lists.create'))->name('task-lists.create');
    Route::post('/lists', [TaskListController::class, 'store'])->name('task-lists.store');
    Route::get('/lists/{taskList}', [TaskListController::class, 'show'])->name('task-lists.show');
});
