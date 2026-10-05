{{-- Suggestions under a shop-name input, or the chosen shop as a chip. Used inside components with the PicksShop trait. --}}
@props(['picked', 'suggestions'])

@if ($picked)
    <div {{ $attributes->class('flex items-center gap-3 rounded-xl border border-verified/30 bg-verified-soft p-3 text-sm') }}>
        <x-heroicon-s-check-badge class="size-5 shrink-0 text-verified" />
        <div class="min-w-0 flex-1">
            <p class="font-semibold">{{ $picked->name }}</p>
            <p class="text-xs text-ink-soft">{{ $picked->location() ? $picked->location().' · ' : '' }}we'll email {{ $picked->maskedEmail() }}</p>
        </div>
        <button type="button" wire:click="clearShop" class="text-xs font-semibold text-ink-soft hover:text-ink">Change</button>
    </div>
@elseif ($suggestions->isNotEmpty())
    <ul {{ $attributes->class('overflow-hidden rounded-xl border border-line bg-surface shadow-lg shadow-ink/5') }}>
        @foreach ($suggestions as $shop)
            <li>
                <button type="button" wire:click="pickShop({{ $shop->id }})" class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm hover:bg-paper">
                    <x-heroicon-o-building-storefront class="size-4 shrink-0 text-muted" />
                    <span class="flex-1"><span class="font-medium">{{ $shop->name }}</span> <span class="text-muted">{{ $shop->location() }}</span></span>
                    <span class="badge-green">Verifies</span>
                </button>
            </li>
        @endforeach
    </ul>
@endif
