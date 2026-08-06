<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-6 py-2.5 bg-[var(--amber)] border border-transparent rounded-lg font-display font-semibold text-sm text-[var(--ink)] hover:bg-[var(--mustard)] focus:bg-[var(--mustard)] active:bg-[var(--mustard)] focus:outline-none focus:ring-2 focus:ring-[var(--amber)] focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
