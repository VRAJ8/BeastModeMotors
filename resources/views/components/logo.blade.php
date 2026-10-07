@props(['mark' => false])

<span {{ $attributes->class('inline-flex items-center gap-2.5') }}>
    <svg viewBox="0 0 32 32" class="size-8 shrink-0" aria-hidden="true">
        <rect width="32" height="32" rx="9" fill="#121417"/>
        <path d="M10 8.5h7.2a4.1 4.1 0 0 1 2.3 7.5 4.4 4.4 0 0 1-2 8.5H10z" fill="none" stroke="#fff" stroke-width="2.6" stroke-linejoin="round"/>
        <path d="M10 16h8" stroke="#fff" stroke-width="2.6"/>
        <circle cx="24.5" cy="24.5" r="3" fill="#ff5b14"/>
    </svg>
    @unless ($mark)
        <span class="leading-none">
            <span class="block font-display text-[15px] font-bold tracking-tight text-ink">Beast Mode Motors</span>
            <span class="mt-0.5 block font-mono text-[9.5px] tracking-[0.2em] text-muted uppercase">Car Passport</span>
        </span>
    @endunless
</span>
