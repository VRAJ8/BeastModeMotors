<div class="card p-6 sm:p-8">
    @if ($sent)
        <div class="py-10 text-center">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-gold/15 text-2xl text-gold">✓</div>
            <h3 class="mt-4 font-display text-4xl tracking-wide text-white">Appraisal requested</h3>
            <p class="mx-auto mt-2 max-w-md text-mist">An appraiser will contact you within one business day to arrange an inspection and a firm offer for your {{ $year }} {{ $make }} {{ $model }}.</p>
        </div>
    @else
        <ol class="mb-8 flex items-center gap-3 text-xs font-semibold tracking-widest uppercase">
            <li class="{{ $step === 1 ? 'text-gold' : 'text-mist' }}">1 · Your car</li>
            <li class="h-px flex-1 bg-steel"></li>
            <li class="{{ $step === 2 ? 'text-gold' : 'text-mist' }}">2 · Estimate & details</li>
        </ol>

        @if ($step === 1)
            <form wire:submit="estimate" class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="ti-make" class="label">Make</label>
                    <input id="ti-make" type="text" wire:model="make" class="input" placeholder="Porsche">
                    @error('make') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ti-model" class="label">Model</label>
                    <input id="ti-model" type="text" wire:model="model" class="input" placeholder="Cayenne Turbo">
                    @error('model') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ti-year" class="label">Year</label>
                    <input id="ti-year" type="number" wire:model="year" class="input" placeholder="2020">
                    @error('year') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ti-mileage" class="label">Mileage</label>
                    <input id="ti-mileage" type="number" step="500" wire:model="mileage" class="input" placeholder="24000">
                    @error('mileage') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ti-price" class="label">Original price (USD)</label>
                    <input id="ti-price" type="number" step="1000" wire:model="originalPrice" class="input" placeholder="135000">
                    @error('originalPrice') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ti-condition" class="label">Condition</label>
                    <select id="ti-condition" wire:model="vehicleCondition" class="input">
                        <option value="excellent">Excellent — like new</option>
                        <option value="good">Good — minor wear</option>
                        <option value="fair">Fair — visible wear</option>
                        <option value="poor">Poor — needs work</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="btn-gold w-full">Get my instant estimate</button>
                </div>
            </form>
        @else
            <div class="mb-8 rounded-xl border border-gold/30 bg-gradient-to-br from-gold/10 to-transparent p-6 text-center">
                <p class="eyebrow">Indicative trade-in value</p>
                <p class="mt-2 font-display text-5xl tracking-wide text-white sm:text-6xl">{{ money($this->valuation['low']) }} – {{ money($this->valuation['high']) }}</p>
                <p class="mt-2 text-sm text-mist">{{ $year }} {{ $make }} {{ $model }} · {{ number_format($mileage) }} mi · {{ ucfirst($vehicleCondition) }}</p>
                <button type="button" wire:click="$set('step', 1)" class="mt-3 text-xs font-semibold tracking-wider text-gold uppercase hover:text-gold-light">Edit details</button>
            </div>

            <form wire:submit="submit" class="grid gap-5 sm:grid-cols-2">
                <p class="text-sm text-mist sm:col-span-2">Lock in a firm offer: leave your details and an appraiser will inspect the car — at your home or our showroom.</p>
                <div>
                    <label for="ti-name" class="label">Full name</label>
                    <input id="ti-name" type="text" wire:model="name" class="input" autocomplete="name">
                    @error('name') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ti-phone" class="label">Phone <span class="normal-case">(optional)</span></label>
                    <input id="ti-phone" type="tel" wire:model="phone" class="input" autocomplete="tel">
                </div>
                <div class="sm:col-span-2">
                    <label for="ti-email" class="label">Email</label>
                    <input id="ti-email" type="email" wire:model="email" class="input" autocomplete="email">
                    @error('email') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="ti-message" class="label">Notes <span class="normal-case">(optional)</span></label>
                    <textarea id="ti-message" wire:model="message" rows="3" class="input" placeholder="Service history, modifications, outstanding finance…"></textarea>
                </div>
                <div class="hidden" aria-hidden="true"><input type="text" wire:model="website" tabindex="-1" autocomplete="off"></div>
                @error('form') <p class="text-sm text-red-400 sm:col-span-2">{{ $message }}</p> @enderror
                <div class="sm:col-span-2">
                    <button type="submit" class="btn-gold w-full">Request firm offer</button>
                </div>
            </form>
        @endif
    @endif
</div>
