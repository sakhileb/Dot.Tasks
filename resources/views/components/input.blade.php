@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-[var(--line)] bg-[var(--paper)] text-[var(--ink)] focus:bg-white focus:border-[var(--amber-ink)] focus:ring-[var(--amber)] rounded-lg shadow-sm']) !!}>
