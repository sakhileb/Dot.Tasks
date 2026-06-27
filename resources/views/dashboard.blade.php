<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Task Lists
            </h2>
            <a href="{{ route('task-lists.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                + New List
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($lists->isEmpty())
                <div class="text-center py-20">
                    <p class="text-gray-500 dark:text-gray-400 mb-4">No task lists yet.</p>
                    <a href="{{ route('task-lists.create') }}"
                       class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                        Create your first list
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($lists as $list)
                        <a href="{{ route('task-lists.show', $list) }}"
                           class="block bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $list->color }}"></div>
                                <h3 class="font-semibold text-gray-900 dark:text-white truncate">{{ $list->name }}</h3>
                            </div>
                            @if($list->description)
                                <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mb-3">{{ $list->description }}</p>
                            @endif
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ $list->tasks_count }} tasks</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
