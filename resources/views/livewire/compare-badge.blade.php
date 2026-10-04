<a href="{{ route('compare') }}" class="relative rounded-md p-2 text-silver transition hover:text-gold" title="Compare cars" wire:navigate>
    <span class="sr-only">Compare ({{ $count }})</span>
    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
    </svg>
    @if ($count)
        <span class="absolute -top-0.5 -right-0.5 grid h-5 w-5 place-items-center rounded-full bg-gold text-[10px] font-bold text-ink">{{ $count }}</span>
    @endif
</a>
