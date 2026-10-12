<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts._head', ['title' => 'Relais, réception et rejeu de webhooks'])
        <meta name="description" content="Relais reçoit, conserve et rejoue vos webhooks : signature vérifiée, nouvelles tentatives automatiques, quotas et API.">
    </head>
    <body>
        <header class="bg-ink text-paper">
            <div class="max-w-6xl mx-auto flex items-center justify-between px-4 sm:px-6 lg:px-8 h-16">
                <a href="/" aria-label="Relais"><x-brand :dark="true" /></a>
                <nav class="flex items-center gap-2 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-ember-500 px-4 py-2 font-semibold text-ink hover:bg-ember-400 transition">Tableau de bord</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 text-paper/80 hover:text-paper hover:bg-ink-700 transition">Se connecter</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-ember-500 px-4 py-2 font-semibold text-ink hover:bg-ember-400 transition">Créer une organisation</a>
                    @endauth
                </nav>
            </div>

            <div class="max-w-6xl mx-auto grid items-center gap-12 px-4 sm:px-6 lg:px-8 pt-16 pb-24 lg:grid-cols-2">
                <div>
                    <p class="kicker !text-ember-400">Réception et rejeu de webhooks</p>
                    <h1 class="mt-4 font-display text-5xl sm:text-6xl font-bold leading-[1.03]">Reçois.<br>Vérifie.<br><span class="text-ember-400">Rejoue.</span></h1>
                    <p class="mt-6 max-w-lg text-lg text-paper/75">Chaque webhook est conservé avec son contenu et sa signature. Quand votre service est en panne, rien n'est perdu : vous rejouez, et Relais réessaie tout seul.</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        @guest
                            <a href="{{ route('register') }}" class="rounded-lg bg-ember-500 px-6 py-3 font-semibold text-ink hover:bg-ember-400 transition">Créer une organisation</a>
                            <a href="{{ route('login') }}" class="rounded-lg border border-paper/25 px-6 py-3 font-semibold text-paper hover:border-ember-400 hover:text-ember-300 transition">Se connecter</a>
                        @endguest
                    </div>
                </div>

                <div class="relative">
                    <svg class="absolute -top-10 -left-6 h-28 w-[110%] opacity-80" viewBox="0 0 520 110" fill="none" aria-hidden="true">
                        <path class="signal-line" d="M10 80 C110 80 120 30 230 30 S 360 80 510 40" stroke="#ef6a2b" stroke-width="2.2" stroke-linecap="round"/>
                        <circle class="signal-node" cx="10" cy="80" r="6" fill="#f6f3ec"/>
                        <circle class="signal-node" cx="230" cy="30" r="6" fill="#ef6a2b" style="animation-delay:.7s"/>
                        <circle class="signal-node" cx="510" cy="40" r="6" fill="#f6f3ec" style="animation-delay:1.4s"/>
                    </svg>
                    <div class="relative mt-16 rounded-xl border border-paper/10 bg-ink-800 p-5 shadow-2xl">
                        <div class="mb-3 flex gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-paper/20"></span><span class="h-2.5 w-2.5 rounded-full bg-paper/20"></span><span class="h-2.5 w-2.5 rounded-full bg-paper/20"></span></div>
<pre class="overflow-x-auto text-[13px] leading-relaxed text-paper/90"><span class="text-paper/50">$</span> curl -X POST {{ url('/h/votre-jeton') }} \
  -H "X-Relais-Signature: sha256=&lt;hmac&gt;" \
  -d '{"evenement":"paiement.reussi"}'

<span class="text-paper/50">{</span>"id": 128, "signature": <span class="text-ember-400">"valid"</span><span class="text-paper/50">}</span></pre>
                    </div>
                </div>
            </div>
        </header>

        <main>
            <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
                <p class="kicker">Ce que fait Relais</p>
                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    <div class="card">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-ember-500/10 text-ember-600" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg>
                        </span>
                        <h3 class="mt-4">Réception sûre</h3>
                        <p class="mt-2 text-sm text-ink-500">Une adresse par source, une signature contrôlée sur le contenu brut, et l'option de refuser tout webhook non signé.</p>
                    </div>
                    <div class="card">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-ember-500/10 text-ember-600" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 15.5-6.2L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15.5 6.2L3 16"/><path d="M3 21v-5h5"/></svg>
                        </span>
                        <h3 class="mt-4">Rejeu fiable</h3>
                        <p class="mt-2 text-sm text-ink-500">Un clic renvoie l'événement d'origine vers votre cible. En cas d'échec, de nouvelles tentatives espacées prennent le relais.</p>
                    </div>
                    <div class="card">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-ember-500/10 text-ember-600" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 14h3"/></svg>
                        </span>
                        <h3 class="mt-4">Équipes isolées</h3>
                        <p class="mt-2 text-sm text-ink-500">Chaque organisation ne voit que ses données. Propriétaires et membres, invitations par lien, quota mensuel et API en lecture.</p>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-ink/10">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-xs text-ink-500">Relais, projet de démonstration. Aucun paiement réel, données d'exemple uniquement.</div>
        </footer>
    </body>
</html>
