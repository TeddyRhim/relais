<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Facturation</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 sm:rounded-lg">{{ session('status') }}</div>
            @endif

            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <h3 class="font-medium text-lg text-gray-900">Consommation</h3>
                <div class="mt-4">
                    @include('_usage-bar', ['used' => $used, 'limit' => $limit, 'percent' => $percent])
                </div>
            </div>

            <div class="p-6 bg-white shadow-sm sm:rounded-lg">
                <h3 class="font-medium text-lg text-gray-900">Plans</h3>
                <ul class="mt-4 divide-y">
                    @foreach ($plans as $plan)
                        <li class="py-4 flex justify-between items-center">
                            <div>
                                <p class="font-medium text-gray-900">{{ $plan->label() }}
                                    @if ($organization->plan === $plan)<span class="ms-2 text-sm text-green-700">plan actuel</span>@endif
                                </p>
                                <p class="text-sm text-gray-600">{{ number_format($plan->monthlyEventLimit(), 0, ',', ' ') }} événements par mois, {{ $plan->price() }}</p>
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
                <p class="mt-4 text-xs text-gray-500">Mode démonstration : aucun paiement réel n'est effectué.</p>
            </div>
        </div>
    </div>
</x-app-layout>
