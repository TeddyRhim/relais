<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-3xl font-bold text-ink">Facturation</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="flash-ok">{{ session('status') }}</div>
            @endif

            <div class="card">
                <h3 class="font-display text-lg font-semibold text-ink">Consommation</h3>
                <div class="mt-4">
                    @include('_usage-bar', ['used' => $used, 'limit' => $limit, 'percent' => $percent])
                </div>
            </div>

            <div class="card">
                <h3 class="font-display text-lg font-semibold text-ink">Plans</h3>
                <ul class="mt-4 divide-y divide-ink/10">
                    @foreach ($plans as $plan)
                        <li class="py-4 flex justify-between items-center">
                            <div>
                                <p class="font-medium text-ink">{{ $plan->label() }}
                                    @if ($organization->plan === $plan)<span class="ms-2 text-sm text-green-700">plan actuel</span>@endif
                                </p>
                                <p class="text-sm text-ink-500">{{ number_format($plan->monthlyEventLimit(), 0, ',', ' ') }} événements par mois, {{ $plan->price() }}</p>
                            </div>
                            @if (auth()->user()->isOwner() && $organization->plan !== $plan)
                                <form method="POST" action="{{ route('billing.update') }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="plan" value="{{ $plan->value }}">
                                    <x-primary-button>Choisir</x-primary-button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if (config('relais.billing.driver') === 'fake')
                    <p class="mt-4 p-3 bg-yellow-50 text-sm text-yellow-900 rounded">Mode démonstration : aucun paiement réel n'est effectué, le changement de plan est immédiat.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
