@php
    $links = [
        ['dashboard', 'Tableau de bord', 'dashboard'],
        ['sources.index', 'Sources', 'sources.*'],
        ['events.index', 'Événements', 'events.*'],
        ['api-keys.index', 'API', 'api-keys.*'],
        ['billing.show', 'Facturation', 'billing.*'],
        ['organization.show', 'Organisation', 'organization.*'],
    ];
@endphp

<nav x-data="{ open: false }" class="bg-ink text-paper">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="shrink-0" aria-label="Relais, tableau de bord">
                    <x-brand :dark="true" />
                </a>

                <div class="hidden lg:flex lg:gap-1">
                    @foreach ($links as [$route, $label, $pattern])
                        <x-nav-link :href="route($route)" :active="request()->routeIs($pattern)">{{ $label }}</x-nav-link>
                    @endforeach
                </div>
            </div>

            <div class="hidden lg:flex lg:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-paper/80 hover:text-paper hover:bg-ink-700 focus:outline-none transition">
                            <span class="grid h-7 w-7 place-items-center rounded-full bg-ember-500 text-xs font-semibold text-ink">{{ \Illuminate\Support\Str::of(Auth::user()->name)->substr(0, 1)->upper() }}</span>
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Profil</x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                Se déconnecter
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-paper/70 hover:text-paper hover:bg-ink-700 focus:outline-none transition" aria-label="Menu">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden border-t border-ink-700">
        <div class="pt-2 pb-3 space-y-1">
            @foreach ($links as [$route, $label, $pattern])
                <x-responsive-nav-link :href="route($route)" :active="request()->routeIs($pattern)">{{ $label }}</x-responsive-nav-link>
            @endforeach
        </div>

        <div class="pt-4 pb-3 border-t border-ink-700">
            <div class="px-4">
                <div class="font-medium text-base text-paper">{{ Auth::user()->name }}</div>
                <div class="text-sm text-paper/60">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">Profil</x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        Se déconnecter
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
