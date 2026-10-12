<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-3xl font-bold text-ink">Événement {{ $event->id }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="flash-ok">{{ session('status') }}</div>
            @endif

            <div class="card space-y-2 text-sm text-ink-600">
                <p>Source : <a class="link" href="{{ route('sources.show', $event->source) }}">{{ $event->source->name }}</a></p>
                <p>Reçu le {{ $event->created_at->format('d/m/Y H:i:s') }} ({{ $event->method }}, {{ $event->ip }})</p>
                <p>Signature : @include('events._signature', ['status' => $event->signature_status])</p>
            </div>

            <div class="card">
                <h3 class="font-display text-lg font-semibold text-ink">Contenu</h3>
                <pre class="mt-3 rounded-lg bg-ink/5 p-3 text-xs overflow-x-auto whitespace-pre-wrap break-all">{{ $event->body }}</pre>
                <h3 class="mt-6 font-display text-lg font-semibold text-ink">En-têtes</h3>
                <pre class="mt-3 rounded-lg bg-ink/5 p-3 text-xs overflow-x-auto">@foreach ($event->headers as $name => $values){{ $name }}: {{ implode(', ', $values) }}
@endforeach</pre>
            </div>

            <div class="card">
                <h3 class="font-display text-lg font-semibold text-ink">Rejouer</h3>
                <form method="POST" action="{{ route('events.replay', $event) }}" class="mt-4 flex gap-4 items-start">
                    @csrf
                    <div class="flex-1">
                        @if (auth()->user()->isOwner())
                            <x-text-input name="target_url" class="block w-full" placeholder="{{ $event->source->target_url ?: 'https://exemple.org/webhook' }}" :value="old('target_url')" />
                        @else
                            <p class="text-sm text-ink-500">Cible : {{ $event->source->target_url ?: 'aucune cible configurée sur la source' }}</p>
                        @endif
                        <x-input-error :messages="$errors->get('target_url')" class="mt-2" />
                    </div>
                    <x-primary-button>Rejouer</x-primary-button>
                </form>

                @if ($replays->isNotEmpty())
                    <div class="mt-6 overflow-x-auto"><table class="tbl">
                        <thead>
                            <tr><th>Date</th><th>Cible</th><th>Statut</th><th>Tentatives</th><th>Réponse</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($replays as $replay)
                                <tr>
                                    <td class="font-mono text-xs">{{ $replay->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td class="break-all font-mono text-xs">{{ $replay->target_url }}</td>
                                    <td><x-badge :tone="['pending' => 'neutral', 'retrying' => 'warn', 'success' => 'ok', 'failed' => 'bad'][$replay->status]">{{ ['pending' => 'en attente', 'retrying' => 'nouvelle tentative', 'success' => 'réussi', 'failed' => 'échoué'][$replay->status] }}</x-badge></td>
                                    <td>{{ $replay->attempts }}</td>
                                    <td>{{ $replay->response_status ?? $replay->error }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
