<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-3xl font-bold text-ink">Source : {{ $source->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="flash-ok">{{ session('status') }}</div>
            @endif

            <div class="card space-y-3">
                <h3 class="font-display text-lg font-semibold text-ink">Réception</h3>
                <p class="text-sm text-ink-500">Envoyer les webhooks en POST à cette adresse.</p>
                <code class="codeblock">{{ $source->receptionUrl() }}</code>
                @if (auth()->user()->isOwner())
                    <p class="text-sm text-ink-500">Secret de signature (HMAC-SHA256 du corps, en hexadécimal, dans l'en-tête {{ $source->signature_header }}) :</p>
                    <code class="codeblock">{{ $source->signing_secret }}</code>
                @endif
            </div>

            @if (auth()->user()->isOwner())
                <div class="card">
                    <h3 class="font-display text-lg font-semibold text-ink">Réglages</h3>
                    <form method="POST" action="{{ route('sources.update', $source) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <x-input-label for="name" value="Nom" />
                            <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $source->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="signature_header" value="En-tête de signature" />
                            <x-text-input id="signature_header" name="signature_header" class="block mt-1 w-full" :value="old('signature_header', $source->signature_header)" />
                            <x-input-error :messages="$errors->get('signature_header')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="target_url" value="Adresse cible du rejeu" />
                            <x-text-input id="target_url" name="target_url" class="block mt-1 w-full" :value="old('target_url', $source->target_url)" />
                            <x-input-error :messages="$errors->get('target_url')" class="mt-2" />
                        </div>
                        <label class="flex items-center gap-2 text-sm text-ink-600">
                            <input type="checkbox" name="require_signature" value="1" @checked(old('require_signature', $source->require_signature))>
                            Refuser les webhooks dont la signature est absente ou invalide
                        </label>
                        <x-primary-button>Enregistrer</x-primary-button>
                    </form>
                    <form method="POST" action="{{ route('sources.destroy', $source) }}" class="mt-6" onsubmit="return confirm('Supprimer cette source et ses événements ?')">
                        @csrf
                        @method('DELETE')
                        <button class="text-sm text-red-600 underline">Supprimer la source</button>
                    </form>
                </div>
            @endif

            <div class="card">
                <h3 class="font-display text-lg font-semibold text-ink">Derniers événements</h3>
                @include('events._list', ['events' => $events])
                <a href="{{ route('events.index', ['source' => $source->id]) }}" class="mt-4 inline-block text-sm link">Tous les événements de cette source</a>
            </div>
        </div>
    </div>
</x-app-layout>
