<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-3xl font-bold text-ink">Sources</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="flash-ok">{{ session('status') }}</div>
            @endif

            @if (auth()->user()->isOwner())
                <div class="card">
                    <h3 class="font-display text-lg font-semibold text-ink">Nouvelle source</h3>
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

            <div class="card">
                @if ($sources->isEmpty())
                    <x-empty-state title="Aucune source pour l'instant" text="Une source donne une adresse de réception et un secret de signature. Créez-en une pour recevoir vos premiers webhooks." />
                @else
                    <ul class="divide-y divide-ink/10">
                        @foreach ($sources as $source)
                            <li class="py-3 flex justify-between items-center">
                                <a href="{{ route('sources.show', $source) }}" class="link">{{ $source->name }}</a>
                                <span class="text-sm text-ink-500">{{ $source->events_count }} événement(s)</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
