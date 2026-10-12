@props(['title', 'text' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center text-center py-10']) }}>
    <svg class="h-14 w-14 text-ink-500/50" viewBox="0 0 56 56" fill="none" aria-hidden="true">
        <circle cx="10" cy="34" r="6" stroke="currentColor" stroke-width="2.4"/>
        <circle cx="46" cy="34" r="6" stroke="currentColor" stroke-width="2.4" stroke-dasharray="3 4"/>
        <path d="M16 34h6M34 34h6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-dasharray="1 5"/>
        <circle cx="28" cy="34" r="4" fill="#ef6a2b" opacity=".55"/>
    </svg>
    <p class="mt-4 font-display text-lg font-semibold text-ink">{{ $title }}</p>
    @if ($text)<p class="mt-1 max-w-sm text-sm text-ink-500">{{ $text }}</p>@endif
    {{ $slot }}
</div>
