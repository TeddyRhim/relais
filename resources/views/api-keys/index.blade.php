<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Clés d'API</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 sm:rounded-lg">{{ session('status') }}</div>
            @endif
            @if (session('api_key'))
                <div class="p-4 bg-yellow-50 text-yellow-900 sm:rounded-lg break-all">
                    Copiez cette clé maintenant, elle ne sera plus affichée : <code>{{ session('api_key') }}</code>
                </div>
            @endif

            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <h3 class="font-medium text-lg text-gray-900">Nouvelle clé</h3>
                <p class="mt-1 text-sm text-gray-600">Lecture seule des sources et des événements, valable 90 jours. Utilisation : <code>Authorization: Bearer &lt;clé&gt;</code> sur <code>{{ url('/api/v1/events') }}</code>.</p>
                <form method="POST" action="{{ route('api-keys.store') }}" class="mt-4 flex gap-4 items-start">
                    @csrf
                    <div class="flex-1">
                        <x-text-input name="name" class="block w-full" placeholder="Nom de la clé" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <x-primary-button>Créer</x-primary-button>
                </form>
            </div>

            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <h3 class="font-medium text-lg text-gray-900">Vos clés</h3>
                @if ($keys->isEmpty())
                    <p class="mt-4 text-gray-600">Aucune clé.</p>
                @else
                    <ul class="mt-4 divide-y">
                        @foreach ($keys as $key)
                            <li class="py-3 flex justify-between items-center text-sm">
                                <span>{{ $key->name }}
                                    <span class="text-gray-500">(expire le {{ $key->expires_at?->format('d/m/Y') ?? 'jamais' }}, dernier usage : {{ $key->last_used_at?->format('d/m/Y H:i') ?? 'aucun' }})</span>
                                </span>
                                <form method="POST" action="{{ route('api-keys.destroy', $key->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 underline">Révoquer</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
