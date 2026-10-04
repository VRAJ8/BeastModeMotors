<div class="flex items-center gap-2">
    <button type="button" wire:click="toggleCompare"
            @class([
                'grid place-items-center rounded-full border transition',
                'h-10 w-10' => $compact,
                'h-12 w-12' => ! $compact,
                'border-gold bg-gold text-ink' => $inCompare,
                'border-steel text-silver hover:border-gold hover:text-gold' => ! $inCompare,
            ])
            aria-pressed="{{ $inCompare ? 'true' : 'false' }}"
            title="{{ $inCompare ? 'Remove from compare' : 'Add to compare' }}">
        <span class="sr-only">{{ $inCompare ? 'Remove from compare' : 'Add to compare' }}</span>
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
        </svg>
    </button>

    <button type="button" wire:click="toggleFavorite"
            @class([
                'grid place-items-center rounded-full border transition',
                'h-10 w-10' => $compact,
                'h-12 w-12' => ! $compact,
                'border-ember bg-ember text-white' => $isFavorite,
                'border-steel text-silver hover:border-ember hover:text-ember' => ! $isFavorite,
            ])
            aria-pressed="{{ $isFavorite ? 'true' : 'false' }}"
            title="{{ $isFavorite ? 'Remove from garage' : 'Save to garage' }}">
        <span class="sr-only">{{ $isFavorite ? 'Remove from garage' : 'Save to garage' }}</span>
        <svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" fill="{{ $isFavorite ? 'currentColor' : 'none' }}" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
        </svg>
    </button>
</div>
