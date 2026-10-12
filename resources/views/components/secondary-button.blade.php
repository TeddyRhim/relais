<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-lg border border-ink/20 bg-white px-4 py-2.5 text-sm font-semibold text-ink shadow-sm hover:border-ember-500 hover:text-ember-700 focus:outline-none focus:ring-2 focus:ring-ember-500 focus:ring-offset-2 focus:ring-offset-paper disabled:opacity-40 transition']) }}>
    {{ $slot }}
</button>
