<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $organization->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-6 bg-white shadow-sm sm:rounded-lg space-y-4">
                <p class="text-gray-900">Plan : <strong>{{ $organization->plan->label() }}</strong>
                    <a href="{{ route('billing.show') }}" class="ms-2 text-sm text-indigo-700 underline">Facturation</a></p>
                @include('_usage-bar', ['used' => $used, 'limit' => $limit, 'percent' => $percent])
            </div>
            <div class="grid grid-cols-2 gap-6">
                <a href="{{ route('sources.index') }}" class="p-6 bg-white shadow-sm sm:rounded-lg block">
                    <p class="text-3xl font-semibold text-gray-900">{{ $sourcesCount }}</p>
                    <p class="text-sm text-gray-600">source(s)</p>
                </a>
                <a href="{{ route('events.index') }}" class="p-6 bg-white shadow-sm sm:rounded-lg block">
                    <p class="text-3xl font-semibold text-gray-900">{{ $repliesCount }}</p>
                    <p class="text-sm text-gray-600">rejeu(x) effectué(s)</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
