<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts._head')
    </head>
    <body>
        <div class="min-h-screen lg:grid lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
            <aside class="hidden lg:flex flex-col justify-between bg-ink text-paper px-12 py-12 relative overflow-hidden">
                <a href="/"><x-brand :dark="true" /></a>

                <div class="relative z-10">
                    <p class="kicker !text-ember-400">Webhooks</p>
                    <h1 class="mt-3 font-display text-5xl font-bold leading-[1.05]">Reçois.<br>Vérifie.<br><span class="text-ember-400">Rejoue.</span></h1>
                    <p class="mt-5 max-w-sm text-paper/70">Chaque webhook est conservé, sa signature contrôlée, et rien n'est perdu quand la cible est en panne.</p>
                </div>

                <svg class="absolute -right-10 bottom-16 w-[34rem] opacity-90" viewBox="0 0 520 160" fill="none" aria-hidden="true">
                    <path class="signal-line" d="M20 110 C120 110 120 50 220 50 S 330 110 430 80" stroke="#ef6a2b" stroke-width="2.4" stroke-linecap="round"/>
                    <circle class="signal-node" cx="20" cy="110" r="7" fill="#f6f3ec"/>
                    <circle class="signal-node" cx="220" cy="50" r="7" fill="#ef6a2b" style="animation-delay:.6s"/>
                    <circle class="signal-node" cx="430" cy="80" r="7" fill="#f6f3ec" style="animation-delay:1.2s"/>
                </svg>

                <p class="relative z-10 text-xs text-paper/50">Projet de démonstration</p>
            </aside>

            <div class="flex min-h-screen flex-col items-center justify-center px-6 py-10">
                <a href="/" class="lg:hidden mb-8"><x-brand /></a>
                <div class="w-full sm:max-w-md card !p-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
