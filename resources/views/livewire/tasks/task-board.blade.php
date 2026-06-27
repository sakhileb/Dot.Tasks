<div>
    <div class="flex justify-end mb-4">
        <button wire:click="$dispatch('open-create-task', { status: 'todo' })"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
            + Add Task
        </button>
    </div>

    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach(\App\Livewire\Tasks\TaskBoard::COLUMNS as $status => $label)
            <div class="shrink-0 w-72 bg-gray-100 dark:bg-gray-900 rounded-xl p-3"
                 x-data
                 x-on:dragover.prevent
                 x-on:drop.prevent="$wire.moveTask($event.dataTransfer.getData('taskId'), '{{ $status }}')">

                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $label }}</h3>
                    <span class="text-xs bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full px-2 py-0.5">
                        {{ $this->tasksByStatus[$status]->count() }}
                    </span>
                </div>

                <div class="space-y-2 min-h-8">
                    @foreach($this->tasksByStatus[$status] as $task)
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-gray-200 dark:border-gray-700 cursor-pointer group"
                             draggable="true"
                             x-on:dragstart="$event.dataTransfer.setData('taskId', '{{ $task->id }}')"
                             wire:click="$dispatch('open-task', { taskId: {{ $task->id }} })">
                            <p class="text-sm text-gray-900 dark:text-white font-medium mb-2">{{ $task->title }}</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xs px-1.5 py-0.5 rounded
                                    {{ match($task->priority) {
                                        'urgent' => 'bg-red-100 text-red-700',
                                        'high'   => 'bg-orange-100 text-orange-700',
                                        'medium' => 'bg-yellow-100 text-yellow-700',
                                        'low'    => 'bg-gray-100 text-gray-600',
                                        default  => 'bg-gray-100 text-gray-600',
                                    } }}">
                                    {{ ucfirst($task->priority) }}
                                </span>
                                @if($task->due_date)
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $task->due_date->format('M j') }}</span>
                                @endif
                            </div>
                            @if($task->subtasks->isNotEmpty())
                                <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">
                                    {{ $task->subtasks->where('status', 'done')->count() }}/{{ $task->subtasks->count() }} subtasks
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <button wire:click="$dispatch('open-create-task', { status: '{{ $status }}' })"
                        class="mt-2 w-full text-xs text-gray-400 dark:text-gray-600 hover:text-gray-600 dark:hover:text-gray-400 py-1 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
                    + Add task
                </button>
            </div>
        @endforeach
    </div>

    <livewire:tasks.create-task :task-list="$taskList" />
</div>
