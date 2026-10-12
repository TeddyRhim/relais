<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Événements</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                @include('events._list', ['events' => $events])
                <div class="mt-4">{{ $events->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
