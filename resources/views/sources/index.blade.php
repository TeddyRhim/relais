<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sources</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 sm:rounded-lg">{{ session('status') }}</div>
            @endif

            @if (auth()->user()->isOwner())
                <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                    <h3 class="font-medium text-lg text-gray-900">Nouvelle source</h3>
                    <form method="POST" action="{{ route('sources.store') }}" class="mt-4 flex gap-4 items-start">
                        @csrf
                        <div class="flex-1">
                            <x-text-input name="name" class="block w-full" placeholder="Nom de la source" :value="old('name')" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <x-primary-button>Créer</x-primary-button>
                    </form>
                </div>
            @endif

            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                @if ($sources->isEmpty())
                    <p class="text-gray-600">Aucune source pour l'instant.</p>
                @else
                    <ul class="divide-y">
                        @foreach ($sources as $source)
                            <li class="py-3 flex justify-between items-center">
                                <a href="{{ route('sources.show', $source) }}" class="text-indigo-700 underline">{{ $source->name }}</a>
                                <span class="text-sm text-gray-600">{{ $source->events_count }} événement(s)</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
