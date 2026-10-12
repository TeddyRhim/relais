@props(['value'])
<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-ink-600']) }}>
    {{ $value ?? $slot }}
</label>
