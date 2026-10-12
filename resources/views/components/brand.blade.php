@props(['dark' => false])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <x-application-logo class="h-8 w-8 {{ $dark ? 'text-paper' : 'text-ink' }}" />
    <span class="font-display text-2xl font-bold tracking-tight {{ $dark ? 'text-paper' : 'text-ink' }}">Relais</span>
</span>
