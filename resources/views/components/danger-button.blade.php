<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-lg bg-brick px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brick/90 focus:outline-none focus:ring-2 focus:ring-brick focus:ring-offset-2 focus:ring-offset-paper transition']) }}>
    {{ $slot }}
</button>
