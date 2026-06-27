<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">← Lists</a>
            <span class="text-gray-300 dark:text-gray-600">/</span>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full" style="background-color: {{ $taskList->color }}"></div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">{{ $taskList->name }}</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:tasks.task-board :task-list="$taskList" />
            <livewire:tasks.task-detail />
        </div>
    </div>
</x-app-layout>
