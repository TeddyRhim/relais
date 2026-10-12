@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-4 pe-4 py-2 border-l-4 border-ember-500 text-start text-base font-medium text-paper bg-ink-700 focus:outline-none transition'
            : 'block w-full ps-4 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-paper/65 hover:text-paper hover:bg-ink-800 focus:outline-none transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
