<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts._head')
    </head>
    <body>
        <div class="min-h-screen">
            @include('layouts.navigation')

            @isset($header)
                <header class="border-b border-ink/10 bg-paper-200/60">
                    <div class="max-w-6xl mx-auto py-7 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot }}
            </main>

            <footer class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 text-xs text-ink-500">
                Relais, projet de démonstration. Données d'exemple uniquement.
            </footer>
        </div>
    </body>
</html>
