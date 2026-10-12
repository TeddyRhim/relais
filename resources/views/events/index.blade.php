<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-3xl font-bold text-ink">Événements</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="card">
                @include('events._list', ['events' => $events])
                <div class="mt-4">{{ $events->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
