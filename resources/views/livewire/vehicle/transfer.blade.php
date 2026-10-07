<section class="card card-pad" id="transfer">
    <h2 class="panel-title">Sold it somewhere else?</h2>
    <p class="mt-1 text-sm text-muted">Sold privately, traded in, or handed down? Send the new owner a transfer link and the passport moves to their garage, just like a sale here. Your running costs and personal documents stay with you.</p>

    @error('transfer') <p class="error mt-3">{{ $message }}</p> @enderror

    @if ($agreed)
        <p class="mt-4 rounded-xl bg-paper p-3 text-sm text-ink-soft">You've agreed a sale for this car in a deal room. Finish or cancel it there first.</p>
    @elseif ($link && $pending)
        <div class="mt-4 space-y-3" x-data="{ copied: false }">
            <p class="text-sm font-medium">Send this link to the new owner</p>
            <div class="flex gap-2">
                <input readonly value="{{ $link }}" class="input num min-w-0 flex-1 text-xs" x-on:focus="$el.select()" aria-label="Transfer link">
                <button type="button" class="btn-secondary btn-sm shrink-0" x-on:click="navigator.clipboard.writeText(@js($link)); copied = true; setTimeout(() => copied = false, 2000)">
                    <x-heroicon-m-clipboard class="size-4" /> <span x-text="copied ? 'Copied' : 'Copy'"></span>
                </button>
            </div>
            <p class="text-xs text-muted">It works once, until {{ $pending->expires_at->format('F j') }}. Copy it now: for safety we don't keep it, so you'd need a new one. They'll type the last {{ \App\Services\PassportTransfers::VIN_TAIL }} characters of the VIN from the car to accept, so send it only to the person who has the car.</p>
        </div>
    @elseif ($pending)
        <p class="mt-4 rounded-xl bg-paper p-3 text-sm">A transfer link at <strong class="num">{{ miles($pending->sale_mileage) }}</strong> is waiting to be accepted. It expires {{ $pending->expires_at->format('F j') }}. Lost it? Make a new one and the old link stops working.</p>
    @endif

    @if ($pending && ! $agreed)
        <div class="mt-3 flex flex-wrap gap-2">
            @if ($disclosure)
                <a href="{{ route('transfers.odometer-disclosure', $pending) }}" class="btn-secondary btn-sm"><x-heroicon-m-document-arrow-down class="size-4" /> Odometer disclosure</a>
            @endif
            <button type="button" wire:click="start" class="btn-ghost btn-sm">New link</button>
            <button type="button" wire:click="cancel" wire:confirm="Cancel this transfer link? It will stop working." class="btn-ghost btn-sm text-danger hover:bg-danger-soft hover:text-danger">Cancel link</button>
        </div>
    @endif

    @if (! $agreed && $starting)
        <form wire:submit="create" class="mt-4 space-y-3">
            <div>
                <label class="label" for="transferMileage">Odometer at handover</label>
                <input id="transferMileage" type="number" wire:model="saleMileage" class="input num">
                @error('sale_mileage') <p class="error">{{ $message }}</p> @enderror
            </div>
            <x-odometer-certification model="odometerStatus" :rollbacks="$rollbacks" />
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="$set('starting', false)" class="btn-ghost btn-sm">Not now</button>
                <button class="btn-primary btn-sm">Make transfer link</button>
            </div>
        </form>
    @elseif (! $agreed && ! $pending)
        <button type="button" wire:click="start" class="btn-secondary mt-4 w-full">Transfer the passport</button>
    @endif

    @unless ($agreed)
        <p class="mt-4 text-xs text-muted">Signing the title over, plates and registration happen with your state's DMV, not here.{{ $disclosure ? ' This car needs a federal odometer disclosure: print it from here once the link is made, and sign it together.' : '' }}</p>
    @endunless
</section>
