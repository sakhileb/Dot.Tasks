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

        <!--
            Kanban board (search + drag/drop board + detail drawer).
            A second, statically-rendered board used to live here, looping
            $taskList->tasks and @include-ing "task-lists._task-card" — a
            partial that doesn't exist in this repo, so that markup threw a
            ViewNotFoundException the moment any column had a task, and it
            fully duplicated what <livewire:tasks.task-board> already does
            correctly. Removed rather than fixed, since the Livewire board is
            the real, interactive implementation.
        -->
        <livewire:tasks.task-board :task-list="$taskList" />
        <livewire:tasks.task-detail />
    </div>
</x-app-layout>
