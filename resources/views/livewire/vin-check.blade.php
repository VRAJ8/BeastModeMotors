<div>
    <form wire:submit="check" class="card flex flex-col gap-3 p-3 shadow-xl shadow-ink/5 sm:flex-row">
        <label for="vin-input" class="sr-only">VIN</label>
        <input id="vin-input" wire:model="vin" maxlength="20" spellcheck="false" autocomplete="off" class="input vin flex-1 border-0 py-3 text-lg uppercase shadow-none focus:ring-0" placeholder="Enter a 17-character VIN">
        <button class="btn-accent px-6 py-3" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="check">Check VIN</span>
            <span wire:loading wire:target="check">Checking…</span>
        </button>
    </form>
    @error('vin') <p class="error mt-3 text-sm">{{ $message }}</p> @enderror
    <p class="mt-3 text-xs text-muted">Try <button type="button" class="vin underline" wire:click="$set('vin', '1HGCM82633A004352')">1HGCM82633A004352</button></p>

    @if ($result)
        @php($d = $result['details'])
        <div class="mt-8 grid gap-6 md:grid-cols-2">
            <section class="card card-pad">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="eyebrow">Decoded</p>
                        <p class="display mt-1 text-2xl">{{ trim(($d['year'] ?? '').' '.($d['make'] ?? 'Unknown make').' '.($d['model'] ?? '')) }}</p>
                        @if (! empty($d['trim']))<p class="text-ink-soft">{{ $d['trim'] }}</p>@endif
                    </div>
                    @if ($result['valid'])
                        <span class="stamp border-verified text-verified">Valid</span>
                    @else
                        <span class="stamp border-danger text-danger">Invalid</span>
                    @endif
                </div>
                <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
                    @foreach (['Body' => $d['body'] ?? null, 'Engine' => $d['engine'] ?? null, 'Drivetrain' => $d['drivetrain'] ?? null, 'Transmission' => $d['transmission'] ?? null, 'Built in' => $result['country'], 'Source' => $result['source'] === 'nhtsa' ? 'NHTSA vPIC' : 'Offline decoder'] as $label => $value)
                        @if ($value)
                            <div><dt class="eyebrow">{{ $label }}</dt><dd class="mt-0.5 font-medium">{{ $value }}</dd></div>
                        @endif
                    @endforeach
                </dl>
                <div class="mt-5 rounded-xl bg-paper p-3 text-sm text-ink-soft">
                    <p class="font-semibold text-ink">Check digit</p>
                    @if ($result['valid'])
                        The 9th character, <span class="vin font-semibold text-ink">{{ $result['vin'][8] }}</span>, matches the weighted checksum of the other 16. The VIN hasn't been mistyped or altered.
                    @else
                        The 9th character should be <span class="vin font-semibold text-ink">{{ $result['check_digit'] }}</span> but is <span class="vin font-semibold text-ink">{{ $result['vin'][8] }}</span>. Either it was mistyped, or the VIN isn't genuine — check it against the car itself.
                    @endif
                </div>
            </section>

            <section class="card card-pad">
                <p class="eyebrow">Safety recalls</p>
                @if ($recalls === null)
                    <p class="mt-3 text-sm text-muted">We couldn't reach NHTSA's recall database just now. You can check at <a class="link" href="https://www.nhtsa.gov/recalls?vin={{ $result['vin'] }}" target="_blank" rel="noopener">nhtsa.gov/recalls</a>.</p>
                @elseif ($recalls === [])
                    <p class="mt-3 flex items-center gap-2"><x-heroicon-s-shield-check class="size-5 text-verified" /> No recalls published for this model year.</p>
                @else
                    <p class="mt-1 text-sm text-muted">{{ count($recalls) }} recall(s) for this model year. Check whether they apply to this exact VIN and were fixed.</p>
                    <ul class="mt-4 max-h-80 space-y-3 overflow-y-auto pr-1">
                        @foreach ($recalls as $recall)
                            <li class="rounded-xl border border-line p-3 text-sm"><p class="font-semibold">{{ $recall['component'] }}</p><p class="vin text-xs text-muted">{{ $recall['campaign_number'] }}</p><p class="mt-1 line-clamp-3 text-ink-soft">{{ $recall['summary'] }}</p></li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <div class="mt-6 flex flex-col items-start justify-between gap-4 rounded-2xl bg-ink p-6 text-white sm:flex-row sm:items-center">
            @if ($listingUrl)
                <p><span class="font-semibold">This car is for sale on Beast Mode Motors</span> with its full passport.</p>
                <a href="{{ $listingUrl }}" class="btn bg-white text-ink hover:bg-paper">See the listing</a>
            @else
                <p><span class="font-semibold">Is this your car?</span> Start a free passport and keep its history in one place.</p>
                <a href="{{ route('register') }}" class="btn-accent">Start a passport</a>
            @endif
        </div>
    @endif
</div>
