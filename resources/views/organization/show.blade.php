<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-3xl font-bold text-ink">Organisation</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="flash-ok">{{ session('status') }}</div>
            @endif
            @if (session('invitation_link'))
                <div class="flash-key break-all">
                    Lien d'invitation (affiché une seule fois) : {{ session('invitation_link') }}
                </div>
            @endif

            <div class="card">
                <h3 class="font-display text-lg font-semibold text-ink">Nom</h3>
                @if (auth()->user()->isOwner())
                    <form method="POST" action="{{ route('organization.update') }}" class="mt-4 flex gap-4 items-start">
                        @csrf
                        @method('PATCH')
                        <div class="flex-1">
                            <x-text-input name="name" class="block w-full" :value="old('name', $organization->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <x-primary-button>Enregistrer</x-primary-button>
                    </form>
                @else
                    <p class="mt-2 text-ink-600">{{ $organization->name }}</p>
                @endif
            </div>

            <div class="card">
                <h3 class="font-display text-lg font-semibold text-ink">Membres</h3>
                <ul class="mt-4 divide-y divide-ink/10">
                    @foreach ($members as $member)
                        <li class="py-3 flex justify-between items-center">
                            <span>{{ $member->name }} <span class="text-ink-500">({{ $member->email }})</span></span>
                            <span class="flex items-center gap-4">
                                <span class="text-sm text-ink-500">{{ $member->role->label() }}</span>
                                @if (auth()->user()->isOwner() && ! $member->isOwner())
                                    <form method="POST" action="{{ route('members.destroy', $member->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-sm text-red-600 underline">Retirer</button>
                                    </form>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if (auth()->user()->isOwner())
                <div class="card">
                    <h3 class="font-display text-lg font-semibold text-ink">Invitations</h3>
                    <form method="POST" action="{{ route('invitations.store') }}" class="mt-4 flex gap-4 items-start">
                        @csrf
                        <div class="flex-1">
                            <x-text-input type="email" name="email" class="block w-full" placeholder="adresse e-mail" :value="old('email')" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <x-primary-button>Inviter</x-primary-button>
                    </form>
                    <ul class="mt-4 divide-y divide-ink/10">
                        @foreach ($invitations as $invitation)
                            <li class="py-3 flex justify-between items-center">
                                <span>{{ $invitation->email }} <span class="text-ink-500">(expire le {{ $invitation->expires_at->format('d/m/Y') }})</span></span>
                                <form method="POST" action="{{ route('invitations.destroy', $invitation) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-sm text-red-600 underline">Annuler</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
