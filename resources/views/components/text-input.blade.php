@props(['disabled' => false])
<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border-ink/20 bg-white text-ink placeholder:text-ink-500/60 shadow-sm focus:border-ember-500 focus:ring-ember-500']) }}>
