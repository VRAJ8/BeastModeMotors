<div class="grid gap-8 lg:grid-cols-[260px_1fr]">
    <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start">
        <div>
            <label for="q" class="label">Search</label>
            <div class="relative">
                <x-heroicon-m-magnifying-glass class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted" />
                <input id="q" wire:model.live.debounce.400ms="q" class="input pl-9" placeholder="Make, model, year…">
            </div>
        </div>

        <div>
            <p class="label">Minimum Passport Score</p>
            <div class="grid grid-cols-4 gap-1.5">
                @foreach ([0 => 'Any', 50 => '50+', 70 => '70+', 85 => '85+'] as $value => $label)
                    <button wire:click="$set('minScore', {{ $value }})" @class(['rounded-lg border px-2 py-1.5 text-xs font-semibold', 'border-ink bg-ink text-white' => $minScore === $value, 'border-line-strong bg-surface hover:border-ink' => $minScore !== $value])>{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-1">
            <div>
                <label for="make" class="label">Make</label>
                <select id="make" wire:model.live="make" class="input">
                    <option value="">Any make</option>
                    @foreach ($makes as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="fuel" class="label">Powertrain</label>
                <select id="fuel" wire:model.live="fuel" class="input">
                    <option value="">Any</option>
                    @foreach ($fuels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="maxPrice" class="label">Max price</label>
                <select id="maxPrice" wire:model.live="maxPrice" class="input">
                    <option value="">No max</option>
                    @foreach ([15000, 25000, 40000, 60000, 90000, 150000] as $p)
                        <option value="{{ $p }}">${{ number_format($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="maxMiles" class="label">Max mileage</label>
                <select id="maxMiles" wire:model.live="maxMiles" class="input">
                    <option value="">No max</option>
                    @foreach ([20000, 40000, 60000, 90000, 120000] as $m)
                        <option value="{{ $m }}">{{ number_format($m) }} mi</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="minYear" class="label">Year from</label>
                <select id="minYear" wire:model.live="minYear" class="input">
                    <option value="">Any</option>
                    @foreach (range(now()->year, now()->year - 25, -1) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="state" class="label">State</label>
                <select id="state" wire:model.live="state" class="input">
                    <option value="">Anywhere</option>
                    @foreach ($states as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($searchable)
            @if ($searchSaved)
                <p class="flex items-center justify-center gap-1.5 rounded-xl bg-verified-soft p-2.5 text-sm text-verified"><x-heroicon-m-bell-alert class="size-4" /> Saved. <a href="{{ route('saved') }}" class="underline">Manage</a></p>
            @else
                <button wire:click="saveSearch" class="btn-secondary w-full"><x-heroicon-m-bell class="size-4" /> Save this search</button>
                <p class="-mt-3 text-center text-xs text-muted">Get an email when a new car matches.</p>
            @endif
            @error('saveSearch') <p class="error">{{ $message }}</p> @enderror
        @endif

        @if ($filtered)
            <button wire:click="clear" class="btn-ghost w-full">Clear filters</button>
        @endif

        <div class="rounded-2xl border border-line bg-surface p-4 text-xs leading-relaxed text-ink-soft">
            <p class="font-semibold text-ink">Every car here has a passport.</p>
            Records, receipts and odometer readings are logged by the owner over time, and shop-verified records are confirmed by the shop that did the work.
        </div>
    </aside>

    <div>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted"><span class="num font-semibold text-ink">{{ $listings->total() }}</span> {{ str('car')->plural($listings->total()) }} for sale</p>
            <label class="flex items-center gap-2 text-sm text-muted">Sort
                <select wire:model.live="sort" class="input w-auto py-1.5">
                    <option value="score">Best history</option>
                    <option value="newest">Newest listings</option>
                    <option value="price_asc">Price: low to high</option>
                    <option value="price_desc">Price: high to low</option>
                    <option value="miles">Lowest mileage</option>
                </select>
            </label>
        </div>

        <div wire:loading.class="opacity-50" class="transition">
            @if ($listings->isEmpty())
                <x-empty icon="heroicon-o-magnifying-glass" title="No cars match" text="Try widening the filters." />
            @else
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($listings as $listing)
                        <x-listing-card :listing="$listing" wire:key="listing-{{ $listing->id }}" />
                    @endforeach
                </div>
                <div class="mt-8">{{ $listings->links() }}</div>
            @endif
        </div>
    </div>
</div>
