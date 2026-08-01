<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\TaskList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskListController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'color'       => 'nullable|string|max:7',
        ]);

        $list = TaskList::create([
            'team_id'     => $request->user()->currentTeam->id,
            'owner_id'    => $request->user()->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'color'       => $validated['color'] ?? '#6366f1',
        ]);

        return redirect()->route('task-lists.show', $list);
    }

    public function show(Request $request, TaskList $taskList): \Illuminate\View\View
    {
        $this->authorize('view', $taskList);

        $labels = Label::where('team_id', $taskList->team_id)->get();

        return view('task-lists.show', compact('taskList', 'labels'));
    }
}
