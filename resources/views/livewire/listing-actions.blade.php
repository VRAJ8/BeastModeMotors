<div class="space-y-4">
    @if ($listing->status === \App\Enums\ListingStatus::Active)
        <form wire:submit="contact" class="space-y-3">
            <label for="message" class="label">Message the seller</label>
            <textarea id="message" wire:model="message" rows="3" class="input"></textarea>
            @error('message') <p class="error">{{ $message }}</p> @enderror
            <button class="btn-accent w-full">{{ auth()->check() ? 'Send & open deal room' : 'Sign in to contact the seller' }}</button>
        </form>
    @else
        <p class="rounded-xl bg-warn-soft p-3 text-sm text-warn">The seller has agreed a sale. If it falls through, the car will be back on the market — save it to hear first.</p>
    @endif

    <div class="flex gap-2">
        <button wire:click="toggleSave" @class(['btn-secondary flex-1', 'border-accent text-accent-dark' => $saved])>
            @if ($saved) <x-heroicon-s-heart class="size-4 text-accent" /> Saved @else <x-heroicon-o-heart class="size-4" /> Save @endif
        </button>
        @auth
            <button wire:click="$toggle('reporting')" class="btn-ghost" title="Report this listing"><x-heroicon-o-flag class="size-4" /> Report</button>
        @endauth
    </div>

    @if ($reporting)
        <form wire:submit="report" class="space-y-3 rounded-xl border border-line bg-paper p-4">
            <p class="text-sm font-semibold">What's wrong?</p>
            <select wire:model="reason" class="input">
                @foreach ($reasons as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <textarea wire:model="details" rows="2" class="input" placeholder="Anything that helps us check (optional)"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="$set('reporting', false)" class="btn-ghost btn-sm">Cancel</button>
                <button class="btn-primary btn-sm">Send report</button>
            </div>
        </form>
    @endif
</div>
