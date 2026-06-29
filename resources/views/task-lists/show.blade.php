<x-app-layout>
    <div style="padding:2rem 2.5rem;">

        <!-- Page header -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;">
            <div style="display:flex;align-items:center;gap:1rem;">
                <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:0.4rem;text-decoration:none;color:#8d90a2;font-size:0.8rem;font-weight:600;transition:color 0.2s;" onmouseover="this.style.color='#b6c4ff'" onmouseout="this.style.color='#8d90a2'">
                    <span class="material-symbols-outlined" style="font-size:16px;">arrow_back</span>
                    Lists
                </a>
                <span style="color:rgba(67,70,86,0.6);">/</span>
                <div style="display:flex;align-items:center;gap:0.6rem;">
                    <div style="width:10px;height:10px;border-radius:9999px;background:{{ $taskList->color ?? '#2962ff' }};"></div>
                    <h1 style="font-family:'Manrope',sans-serif;font-size:1.25rem;font-weight:800;color:#dae2fd;margin:0;">{{ $taskList->name }}</h1>
                </div>
            </div>
        </div>

        <!-- Kanban board -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1.25rem;align-items:start;">

            <!-- Todo -->
            <div style="background:rgba(19,27,46,0.9);border:1px solid rgba(67,70,86,0.25);border-radius:1rem;overflow:hidden;">
                <div style="padding:1rem 1.25rem;border-bottom:1px solid rgba(67,70,86,0.2);display:flex;align-items:center;gap:0.6rem;">
                    <div style="width:8px;height:8px;border-radius:9999px;background:#8d90a2;"></div>
                    <span style="font-family:'Manrope',sans-serif;font-size:0.78rem;font-weight:700;color:#b7c8e1;text-transform:uppercase;letter-spacing:0.1em;">Todo</span>
                    <span style="margin-left:auto;font-size:0.7rem;color:#8d90a2;background:rgba(67,70,86,0.3);border-radius:9999px;padding:0.1rem 0.5rem;">
                        {{ $taskList->tasks->where('status','todo')->count() }}
                    </span>
                </div>
                <div style="padding:0.75rem;display:flex;flex-direction:column;gap:0.6rem;min-height:120px;">
                    @forelse($taskList->tasks->where('status','todo') as $task)
                        @include('task-lists._task-card', ['task' => $task])
                    @empty
                        <div style="padding:1.5rem 0;text-align:center;">
                            <span style="font-size:0.75rem;color:rgba(141,144,162,0.5);">No tasks here</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- In Progress -->
            <div style="background:rgba(19,27,46,0.9);border:1px solid rgba(67,70,86,0.25);border-radius:1rem;overflow:hidden;">
                <div style="padding:1rem 1.25rem;border-bottom:1px solid rgba(67,70,86,0.2);display:flex;align-items:center;gap:0.6rem;">
                    <div style="width:8px;height:8px;border-radius:9999px;background:#d97706;"></div>
                    <span style="font-family:'Manrope',sans-serif;font-size:0.78rem;font-weight:700;color:#b7c8e1;text-transform:uppercase;letter-spacing:0.1em;">In Progress</span>
                    <span style="margin-left:auto;font-size:0.7rem;color:#8d90a2;background:rgba(67,70,86,0.3);border-radius:9999px;padding:0.1rem 0.5rem;">
                        {{ $taskList->tasks->where('status','in_progress')->count() }}
                    </span>
                </div>
                <div style="padding:0.75rem;display:flex;flex-direction:column;gap:0.6rem;min-height:120px;">
                    @forelse($taskList->tasks->where('status','in_progress') as $task)
                        @include('task-lists._task-card', ['task' => $task])
                    @empty
                        <div style="padding:1.5rem 0;text-align:center;">
                            <span style="font-size:0.75rem;color:rgba(141,144,162,0.5);">No tasks here</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Review -->
            <div style="background:rgba(19,27,46,0.9);border:1px solid rgba(67,70,86,0.25);border-radius:1rem;overflow:hidden;">
                <div style="padding:1rem 1.25rem;border-bottom:1px solid rgba(67,70,86,0.2);display:flex;align-items:center;gap:0.6rem;">
                    <div style="width:8px;height:8px;border-radius:9999px;background:#7c3aed;"></div>
                    <span style="font-family:'Manrope',sans-serif;font-size:0.78rem;font-weight:700;color:#b7c8e1;text-transform:uppercase;letter-spacing:0.1em;">Review</span>
                    <span style="margin-left:auto;font-size:0.7rem;color:#8d90a2;background:rgba(67,70,86,0.3);border-radius:9999px;padding:0.1rem 0.5rem;">
                        {{ $taskList->tasks->where('status','review')->count() }}
                    </span>
                </div>
                <div style="padding:0.75rem;display:flex;flex-direction:column;gap:0.6rem;min-height:120px;">
                    @forelse($taskList->tasks->where('status','review') as $task)
                        @include('task-lists._task-card', ['task' => $task])
                    @empty
                        <div style="padding:1.5rem 0;text-align:center;">
                            <span style="font-size:0.75rem;color:rgba(141,144,162,0.5);">No tasks here</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Done -->
            <div style="background:rgba(19,27,46,0.9);border:1px solid rgba(67,70,86,0.25);border-radius:1rem;overflow:hidden;">
                <div style="padding:1rem 1.25rem;border-bottom:1px solid rgba(67,70,86,0.2);display:flex;align-items:center;gap:0.6rem;">
                    <div style="width:8px;height:8px;border-radius:9999px;background:#059669;"></div>
                    <span style="font-family:'Manrope',sans-serif;font-size:0.78rem;font-weight:700;color:#b7c8e1;text-transform:uppercase;letter-spacing:0.1em;">Done</span>
                    <span style="margin-left:auto;font-size:0.7rem;color:#8d90a2;background:rgba(67,70,86,0.3);border-radius:9999px;padding:0.1rem 0.5rem;">
                        {{ $taskList->tasks->where('status','done')->count() }}
                    </span>
                </div>
                <div style="padding:0.75rem;display:flex;flex-direction:column;gap:0.6rem;min-height:120px;">
                    @forelse($taskList->tasks->where('status','done') as $task)
                        @include('task-lists._task-card', ['task' => $task])
                    @empty
                        <div style="padding:1.5rem 0;text-align:center;">
                            <span style="font-size:0.75rem;color:rgba(141,144,162,0.5);">No tasks here</span>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Livewire components (task board / detail panel) -->
        <div style="margin-top:2rem;">
            <livewire:tasks.task-board :task-list="$taskList" />
            <livewire:tasks.task-detail />
        </div>
    </div>
</x-app-layout>
