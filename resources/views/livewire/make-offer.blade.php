<div>
    @if ($sent)
        <div class="py-6 text-center">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-gold/15 text-2xl text-gold">✓</div>
            <h3 class="mt-4 font-display text-3xl tracking-wide text-white">Offer received</h3>
            <p class="mt-2 text-mist">We'll respond to your {{ money($amount) }} offer within one business day.</p>
        </div>
    @else
        <form wire:submit="submit" class="space-y-4">
            <div>
                <label for="offer-amount" class="label">Your offer (USD)</label>
                <div class="relative">
                    <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-mist">$</span>
                    <input id="offer-amount" type="number" step="1000" wire:model="amount" class="input pl-7 text-lg font-semibold">
                </div>
                <p class="mt-1 text-xs text-mist">Asking {{ money($vehicle->price) }}</p>
                @error('amount') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="offer-name" class="label">Full name</label>
                    <input id="offer-name" type="text" wire:model="name" class="input" autocomplete="name">
                    @error('name') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="offer-phone" class="label">Phone <span class="normal-case">(optional)</span></label>
                    <input id="offer-phone" type="tel" wire:model="phone" class="input" autocomplete="tel">
                </div>
            </div>
            <div>
                <label for="offer-email" class="label">Email</label>
                <input id="offer-email" type="email" wire:model="email" class="input" autocomplete="email">
                @error('email') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="offer-message" class="label">Message <span class="normal-case">(optional)</span></label>
                <textarea id="offer-message" wire:model="message" rows="2" class="input" placeholder="Cash buyer, trade-in, timeline…"></textarea>
            </div>
            <div class="hidden" aria-hidden="true"><input type="text" wire:model="website" tabindex="-1" autocomplete="off"></div>
            @error('form') <p class="text-sm text-red-400">{{ $message }}</p> @enderror
            <button type="submit" class="btn-gold w-full" wire:loading.attr="disabled">Send offer</button>
        </form>
    @endif
</div>
