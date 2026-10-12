@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-paper bg-ink-700 focus:outline-none transition'
            : 'inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-paper/65 hover:text-paper hover:bg-ink-800 focus:outline-none transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
