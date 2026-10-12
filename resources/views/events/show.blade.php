<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Événement {{ $event->id }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 sm:rounded-lg">{{ session('status') }}</div>
            @endif

            <div class="p-6 bg-white shadow-sm sm:rounded-lg space-y-2 text-sm text-gray-700">
                <p>Source : <a class="text-indigo-700 underline" href="{{ route('sources.show', $event->source) }}">{{ $event->source->name }}</a></p>
                <p>Reçu le {{ $event->created_at->format('d/m/Y H:i:s') }} ({{ $event->method }}, {{ $event->ip }})</p>
                <p>Signature : {{ ['valid' => 'valide', 'invalid' => 'invalide', 'missing' => 'absente'][$event->signature_status] }}</p>
            </div>

            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <h3 class="font-medium text-lg text-gray-900">Contenu</h3>
                <pre class="mt-3 p-3 bg-gray-100 rounded text-xs overflow-x-auto whitespace-pre-wrap break-all">{{ $event->body }}</pre>
                <h3 class="mt-6 font-medium text-lg text-gray-900">En-têtes</h3>
                <pre class="mt-3 p-3 bg-gray-100 rounded text-xs overflow-x-auto">@foreach ($event->headers as $name => $values){{ $name }}: {{ implode(', ', $values) }}
@endforeach</pre>
            </div>

            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <h3 class="font-medium text-lg text-gray-900">Rejouer</h3>
                <form method="POST" action="{{ route('events.replay', $event) }}" class="mt-4 flex gap-4 items-start">
                    @csrf
                    <div class="flex-1">
                        <x-text-input name="target_url" class="block w-full" placeholder="{{ $event->source->target_url ?: 'https://exemple.org/webhook' }}" :value="old('target_url')" />
                        <x-input-error :messages="$errors->get('target_url')" class="mt-2" />
                    </div>
                    <x-primary-button>Rejouer</x-primary-button>
                </form>

                @if ($replays->isNotEmpty())
                    <table class="mt-6 w-full text-sm text-left">
                        <thead class="text-gray-500">
                            <tr><th class="py-2">Date</th><th>Cible</th><th>Statut</th><th>Tentatives</th><th>Réponse</th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($replays as $replay)
                                <tr>
                                    <td class="py-2">{{ $replay->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td class="break-all">{{ $replay->target_url }}</td>
                                    <td>{{ ['pending' => 'en attente', 'retrying' => 'nouvelle tentative', 'success' => 'réussi', 'failed' => 'échoué'][$replay->status] }}</td>
                                    <td>{{ $replay->attempts }}</td>
                                    <td>{{ $replay->response_status ?? $replay->error }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
