<x-app-layout>
    <x-slot name="header">
        <p class="kicker">Organisation</p>
        <h2 class="mt-1 font-display text-3xl font-bold text-ink">{{ $organization->name }}</h2>
    </x-slot>

    @php $max = max(1, collect($days)->max('count')); @endphp

    <div class="py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="card lg:col-span-2 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3>Quota du mois</h3>
                        <span class="text-sm text-ink-500">Plan <x-badge tone="ember" class="ms-1">{{ $organization->plan->label() }}</x-badge>
                            <a href="{{ route('billing.show') }}" class="link ms-2">Facturation</a></span>
                    </div>
                    @include('_usage-bar', ['used' => $used, 'limit' => $limit, 'percent' => $percent])
                    <p class="text-sm text-ink-500">Le compteur repart à zéro le {{ now()->addMonthNoOverflow()->startOfMonth()->format('d/m/Y') }}. Au quota, les nouveaux webhooks sont refusés.</p>
                </div>

                <div class="grid grid-cols-2 gap-6 lg:grid-cols-1">
                    <a href="{{ route('sources.index') }}" class="card block transition hover:border-ember-500/60">
                        <p class="font-display text-4xl font-bold text-ink">{{ $sourcesCount }}</p>
                        <p class="text-sm text-ink-500">source(s)</p>
                    </a>
                    <a href="{{ route('events.index') }}" class="card block transition hover:border-ember-500/60">
                        <p class="font-display text-4xl font-bold text-ink">{{ $repliesCount }}</p>
                        <p class="text-sm text-ink-500">rejeu(x) effectué(s)</p>
                    </a>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-5">
                <div class="card flex flex-col lg:col-span-3">
                    <h3>Événements reçus, 14 derniers jours</h3>
                    <div class="mt-6 flex min-h-[10rem] flex-1 items-end gap-1.5" role="img" aria-label="Événements reçus par jour : {{ collect($days)->map(fn ($d) => $d['label'].' '.$d['count'])->implode(', ') }}">
                        @foreach ($days as $day)
                            <div class="flex h-full flex-1 flex-col justify-end" title="{{ $day['label'] }} : {{ $day['count'] }}">
                                <div class="w-full rounded-t {{ $day['count'] > 0 ? 'bg-gradient-to-t from-ember-600 to-ember-400' : 'bg-ink/10' }}" style="height: {{ $day['count'] > 0 ? max(4, round($day['count'] / $max * 100)) : 2 }}%"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex justify-between font-mono text-[11px] text-ink-500">
                        <span>{{ $days[0]['label'] }}</span><span>{{ $days[count($days) - 1]['label'] }}</span>
                    </div>
                </div>

                <div class="card lg:col-span-2">
                    <div class="flex items-center justify-between">
                        <h3>Derniers événements</h3>
                        <a href="{{ route('events.index') }}" class="link text-sm">Tout voir</a>
                    </div>
                    @if ($recent->isEmpty())
                        <x-empty-state title="Rien reçu pour l'instant" text="Envoyez un premier webhook à l'adresse d'une source." />
                    @else
                        <ul class="mt-4 divide-y divide-ink/10">
                            @foreach ($recent as $event)
                                <li>
                                    <a href="{{ route('events.show', $event) }}" class="-mx-2 flex items-center justify-between gap-3 rounded-lg px-2 py-2.5 hover:bg-paper">
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-medium text-ink">{{ $event->source->name }}</span>
                                            <span class="block font-mono text-xs text-ink-500">{{ $event->created_at->format('d/m H:i:s') }}</span>
                                        </span>
                                        @include('events._signature', ['status' => $event->signature_status])
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
