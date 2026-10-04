<div class="mx-auto max-w-3xl space-y-6">
    <form wire:submit="save" class="card card-pad">
        <h2 class="panel-title">Car details</h2>
        <p class="text-sm text-muted">The VIN, year, make and model come from the VIN and can't be changed — they're what makes the history trustworthy.</p>
        <dl class="mt-5 grid grid-cols-2 gap-4 rounded-xl bg-paper p-4 text-sm sm:grid-cols-4">
            <div class="col-span-2"><dt class="eyebrow">VIN</dt><dd class="vin mt-0.5 font-medium">{{ $vehicle->vin }}</dd></div>
            <div><dt class="eyebrow">Year</dt><dd class="mt-0.5 font-medium">{{ $vehicle->year }}</dd></div>
            <div><dt class="eyebrow">Make / model</dt><dd class="mt-0.5 font-medium">{{ $vehicle->make }} {{ $vehicle->model }}</dd></div>
        </dl>
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            @foreach (['nickname' => 'Nickname (private)', 'trim' => 'Trim / version', 'exterior_color' => 'Colour', 'engine' => 'Engine', 'transmission' => 'Transmission', 'drivetrain' => 'Drivetrain'] as $field => $label)
                <div>
                    <label class="label" for="{{ $field }}">{{ $label }}</label>
                    <input id="{{ $field }}" wire:model="{{ $field }}" class="input">
                    @error($field) <p class="error">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <div>
                <label class="label" for="fuel">Powertrain</label>
                <select id="fuel" wire:model="fuel_type" class="input">
                    @foreach ($fuels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-6 flex justify-end"><button class="btn-primary">Save details</button></div>
    </form>

    <form wire:submit="delete" class="card card-pad border-danger/30">
        <h2 class="panel-title text-danger">Delete this passport</h2>
        <p class="mt-1 text-sm text-ink-soft">Removes the car, every record, receipt and photo. If you've sold the car, sell it through a deal instead so the history goes to the new owner.</p>
        <div class="mt-4 flex flex-wrap items-end gap-3">
            <div>
                <label class="label" for="confirmVin">Type <span class="vin">{{ substr($vehicle->vin, -6) }}</span> to confirm</label>
                <input id="confirmVin" wire:model="confirmVin" class="input vin w-48 uppercase" autocomplete="off">
            </div>
            <button class="btn-danger">Delete permanently</button>
        </div>
        @error('confirmVin') <p class="error">{{ $message }}</p> @enderror
    </form>
</div>
