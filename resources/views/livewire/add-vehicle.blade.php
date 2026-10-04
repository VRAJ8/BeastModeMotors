<div class="mx-auto max-w-2xl">
    <ol class="mb-8 grid grid-cols-3 gap-2 text-xs font-medium">
        @foreach (['Identify', 'Confirm details', 'Your ownership'] as $i => $label)
            <li @class(['flex items-center gap-2 border-t-2 pt-3', 'border-ink text-ink' => $step >= $i + 1, 'border-line text-muted' => $step < $i + 1])>
                <span class="num">0{{ $i + 1 }}</span> {{ $label }}
            </li>
        @endforeach
    </ol>

    @if ($step === 1)
        <form wire:submit="decode" class="card card-pad">
            <h2 class="display text-2xl">What's the VIN?</h2>
            <p class="mt-1 text-sm text-ink-soft">The 17-character code on the driver-side door jamb, at the base of the windshield, or on your registration.</p>

            <label for="vin" class="label mt-6">Vehicle Identification Number</label>
            <input id="vin" wire:model="vin" maxlength="20" autocomplete="off" autofocus spellcheck="false"
                   class="input vin py-3 text-lg uppercase" placeholder="e.g. 1HGCM82633A004352">
            @error('vin') <p class="error">{{ $message }}</p> @enderror

            <div class="mt-6 flex items-center justify-between gap-4">
                <p class="flex items-center gap-1.5 text-xs text-muted"><x-heroicon-m-lock-closed class="size-3.5" /> Decoded with NHTSA open data. Your VIN is never shown publicly unless you share it.</p>
                <button class="btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="decode">Decode VIN</span>
                    <span wire:loading wire:target="decode">Decoding…</span>
                </button>
            </div>
        </form>
    @endif

    @if ($step === 2)
        <form wire:submit="confirmDetails" class="card card-pad">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="display text-2xl">Is this your car?</h2>
                    <p class="vin mt-1 text-sm text-muted">{{ $vin }}</p>
                </div>
                @if ($lookup['valid'])
                    <span class="stamp border-verified text-verified"><x-heroicon-s-check-badge class="size-3.5" /> Check digit valid</span>
                @else
                    <span class="stamp border-warn text-warn">Check digit mismatch</span>
                @endif
            </div>

            @unless ($lookup['valid'])
                <p class="mt-4 rounded-xl bg-warn-soft p-3 text-sm text-warn">The 9th character should be <strong class="vin">{{ $lookup['check_digit'] }}</strong> for this VIN. Double-check for typos — a VIN that fails this test lowers the Passport Score.</p>
            @endunless

            @if ($lookup['source'] === 'offline')
                <p class="mt-4 rounded-xl bg-paper p-3 text-sm text-ink-soft">We couldn't reach the NHTSA database, so we decoded what we could offline. Please fill in the rest.</p>
            @endif

            <div class="mt-6 grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-2">
                    <label class="label" for="year">Year</label>
                    <input id="year" type="number" wire:model="year" class="input num">
                    @error('year') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="make">Make</label>
                    <input id="make" wire:model="make" class="input">
                    @error('make') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="model">Model</label>
                    <input id="model" wire:model="model" class="input">
                    @error('model') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-3">
                    <label class="label" for="trim">Trim / version</label>
                    <input id="trim" wire:model="trim" class="input" placeholder="e.g. Carrera S">
                </div>
                <div class="sm:col-span-3">
                    <label class="label" for="fuel_type">Powertrain</label>
                    <select id="fuel_type" wire:model="fuel_type" class="input">
                        @foreach ($fuels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="hint">Sets the default maintenance schedule.</p>
                </div>
                <div class="sm:col-span-3">
                    <label class="label" for="exterior_color">Colour</label>
                    <input id="exterior_color" wire:model="exterior_color" class="input" placeholder="e.g. Chalk">
                </div>
                <div class="sm:col-span-3">
                    <label class="label" for="nickname">Nickname <span class="font-normal text-muted">(optional, private)</span></label>
                    <input id="nickname" wire:model="nickname" class="input" placeholder="e.g. The weekend car">
                </div>
            </div>

            @if ($engine || $drivetrain || $transmission || $body)
                <dl class="mt-6 grid grid-cols-2 gap-3 rounded-xl bg-paper p-4 text-sm sm:grid-cols-4">
                    @foreach (['Body' => $body, 'Engine' => $engine, 'Drivetrain' => $drivetrain, 'Transmission' => $transmission] as $label => $value)
                        @if ($value)
                            <div><dt class="eyebrow">{{ $label }}</dt><dd class="mt-0.5 font-medium">{{ $value }}</dd></div>
                        @endif
                    @endforeach
                </dl>
            @endif

            <div class="mt-6 flex justify-between">
                <button type="button" wire:click="back" class="btn-ghost">Back</button>
                <button class="btn-primary">Looks right</button>
            </div>
        </form>
    @endif

    @if ($step === 3)
        <form wire:submit="save" class="card card-pad">
            <h2 class="display text-2xl">Your time with it</h2>
            <p class="mt-1 text-sm text-ink-soft">This starts your chapter of the car's history. Purchase price stays private.</p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="acquired_via">How did you get it?</label>
                    <select id="acquired_via" wire:model="acquired_via" class="input">
                        @foreach ($acquisitions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="started_on">When?</label>
                    <input id="started_on" type="date" wire:model="started_on" max="{{ now()->toDateString() }}" class="input">
                    @error('started_on') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="start_mileage">Odometer when you got it</label>
                    <div class="relative">
                        <input id="start_mileage" type="number" min="0" wire:model="start_mileage" class="input num pr-10">
                        <span class="absolute inset-y-0 right-3 grid place-items-center text-xs text-muted">mi</span>
                    </div>
                    @error('start_mileage') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="current_mileage">Odometer today</label>
                    <div class="relative">
                        <input id="current_mileage" type="number" min="0" wire:model="current_mileage" class="input num pr-10">
                        <span class="absolute inset-y-0 right-3 grid place-items-center text-xs text-muted">mi</span>
                    </div>
                    @error('current_mileage') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="purchase_price">What you paid <span class="font-normal text-muted">(optional, private)</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 grid place-items-center text-sm text-muted">$</span>
                        <input id="purchase_price" inputmode="decimal" wire:model="purchase_price" class="input num pl-7">
                    </div>
                    <p class="hint">Used for your own cost-of-ownership figures. Never shown to anyone else.</p>
                    @error('purchase_price') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-between">
                <button type="button" wire:click="back" class="btn-ghost">Back</button>
                <button class="btn-accent" wire:loading.attr="disabled">Create passport</button>
            </div>
        </form>
    @endif
</div>
