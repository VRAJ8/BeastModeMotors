<div>
    @include('transfers.partials.car', ['transfer' => $transfer])

    <section class="card card-pad mt-6">
        @if ($problem)
            <p class="text-sm text-ink-soft">{{ $problem }}</p>
            <a href="{{ route('garage') }}" class="btn-secondary btn-sm mt-4">Go to my garage</a>
        @else
            <h2 class="panel-title">Accept the passport</h2>
            <p class="mt-1 rounded-xl bg-paper p-3 text-sm">{{ $transfer->sender->publicName() }} recorded <strong class="num">{{ miles($transfer->sale_mileage) }}</strong> at handover and certified it as <strong>{{ Str::lower($transfer->odometer_status->label()) }}</strong>. Check it against the dashboard before you accept.</p>

            <form wire:submit="accept" class="mt-4 space-y-4">
                <div>
                    <label class="label" for="vinTail">Last {{ \App\Services\PassportTransfers::VIN_TAIL }} characters of the VIN</label>
                    <input id="vinTail" wire:model="vinTail" maxlength="{{ \App\Services\PassportTransfers::VIN_TAIL }}" autocomplete="off" autocapitalize="characters" class="input num uppercase">
                    <p class="mt-1 text-xs text-muted">Read it off the car: the plate at the bottom of the windshield, or the sticker in the driver's door frame.</p>
                    @error('vinTail') <p class="error">{{ $message }}</p> @enderror
                    @error('vin_tail') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="acquiredVia">How did you get it?</label>
                    <select id="acquiredVia" wire:model="acquiredVia" class="input">
                        @foreach ($via as $option)
                            <option value="{{ $option->value }}">{{ $option->getLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="price">What you paid <span class="font-normal text-muted">(optional, private to you)</span></label>
                    <input id="price" type="number" step="0.01" min="0" wire:model="price" class="input num" placeholder="0.00">
                    @error('price') <p class="error">{{ $message }}</p> @enderror
                </div>
                @error('transfer') <p class="error">{{ $message }}</p> @enderror
                <button class="btn-accent w-full">Accept the passport</button>
            </form>
            <p class="mt-4 text-xs text-muted">You still title and register the car with your state's DMV. This moves its history, not its legal ownership.</p>
            <button type="button" wire:click="decline" wire:confirm="Decline this passport? The link will stop working." class="mt-3 text-sm text-muted hover:text-danger">This isn't my car — decline</button>
        @endif
    </section>
</div>
