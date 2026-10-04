<div class="container-x py-10" x-data="{ filtersOpen: false }">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative flex-1 sm:max-w-md">
            <label for="search" class="sr-only">Search inventory</label>
            <svg class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-mist" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
            <input id="search" type="search" wire:model.live.debounce.400ms="search" placeholder="Search make, model, engine, colour…" class="input py-3 pl-10">
        </div>

        <div class="flex items-center gap-3">
            <button type="button" @click="filtersOpen = true" class="btn-outline px-4 py-2.5 lg:hidden">
                Filters
                @if ($this->activeFilterCount)
                    <span class="grid h-5 w-5 place-items-center rounded-full bg-gold text-[10px] text-ink">{{ $this->activeFilterCount }}</span>
                @endif
            </button>
            <label for="sort" class="sr-only">Sort by</label>
            <select id="sort" wire:model.live="sort" class="input w-auto py-2.5 pr-10">
                @foreach (\App\Livewire\Inventory::SORTS as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-[17rem_1fr]">
        {{-- Filters: sidebar on desktop, slide-over on mobile --}}
        <div x-cloak x-show="filtersOpen" x-transition.opacity class="fixed inset-0 z-40 bg-black/70 lg:hidden" @click="filtersOpen = false"></div>
        <aside :class="filtersOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 w-80 max-w-[85vw] overflow-y-auto bg-carbon p-6 transition-transform duration-300 lg:static lg:z-auto lg:w-auto lg:max-w-none lg:translate-x-0 lg:overflow-visible lg:bg-transparent lg:p-0"
               aria-label="Filters">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="font-display text-2xl tracking-wide text-white">Filters</h2>
                @if ($this->activeFilterCount)
                    <button type="button" wire:click="clearFilters" class="text-xs font-semibold tracking-wider text-gold uppercase hover:text-gold-light">Clear all</button>
                @endif
                <button type="button" @click="filtersOpen = false" class="text-mist lg:hidden" aria-label="Close filters">✕</button>
            </div>

            <div class="space-y-7">
                <fieldset>
                    <legend class="label">Make</legend>
                    <div class="mt-2 space-y-2">
                        @foreach ($this->brandOptions as $brand)
                            <label class="flex cursor-pointer items-center justify-between gap-2 text-sm">
                                <span class="flex items-center gap-2.5">
                                    <input type="checkbox" value="{{ $brand->slug }}" wire:model.live="brands" class="rounded border-steel bg-graphite text-gold focus:ring-gold focus:ring-offset-ink">
                                    <span class="text-silver">{{ $brand->name }}</span>
                                </span>
                                <span class="text-xs text-mist">{{ $brand->vehicles_count }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div>
                    <label for="body" class="label">Body style</label>
                    <select id="body" wire:model.live="body" class="input">
                        <option value="">Any</option>
                        @foreach (\App\Enums\BodyType::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->getLabel() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="condition" class="label">Condition</label>
                    <select id="condition" wire:model.live="condition" class="input">
                        <option value="">Any</option>
                        @foreach (\App\Enums\Condition::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->getLabel() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="fuel" class="label">Powertrain</label>
                    <select id="fuel" wire:model.live="fuel" class="input">
                        <option value="">Any</option>
                        @foreach (\App\Enums\FuelType::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->getLabel() }}</option>
                        @endforeach
                    </select>
                </div>

                <fieldset>
                    <legend class="label">Price (USD)</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" min="0" step="10000" wire:model.live.debounce.600ms="minPrice" placeholder="Min" class="input" aria-label="Minimum price">
                        <input type="number" min="0" step="10000" wire:model.live.debounce.600ms="maxPrice" placeholder="Max" class="input" aria-label="Maximum price">
                    </div>
                </fieldset>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="min_year" class="label">Year from</label>
                        <select id="min_year" wire:model.live="minYear" class="input">
                            <option value="">Any</option>
                            @foreach (range((int) date('Y'), 2015) as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="min_hp" class="label">Min HP</label>
                        <select id="min_hp" wire:model.live="minHp" class="input">
                            <option value="">Any</option>
                            @foreach ([400, 500, 600, 700, 800, 1000] as $hp)
                                <option value="{{ $hp }}">{{ $hp }}+</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-silver">
                    <input type="checkbox" wire:model.live="includeSold" class="rounded border-steel bg-graphite text-gold focus:ring-gold focus:ring-offset-ink">
                    Include recently sold
                </label>

                <button type="button" @click="filtersOpen = false" class="btn-gold w-full lg:hidden">Show {{ $vehicles->total() }} cars</button>
            </div>
        </aside>

        <section aria-live="polite">
            <div class="mb-5 flex flex-wrap items-center gap-2">
                <p class="mr-2 text-sm text-mist"><span class="font-semibold text-white">{{ $vehicles->total() }}</span> {{ str('vehicle')->plural($vehicles->total()) }}</p>
                @foreach ($brands as $slug)
                    <button type="button" wire:click="removeBrand('{{ $slug }}')" class="chip hover:border-gold">{{ $this->brandOptions->firstWhere('slug', $slug)?->name ?? $slug }} <span aria-hidden="true">✕</span></button>
                @endforeach
                @if ($body)
                    <button type="button" wire:click="$set('body', '')" class="chip hover:border-gold">{{ \App\Enums\BodyType::tryFrom($body)?->getLabel() }} <span aria-hidden="true">✕</span></button>
                @endif
                @if ($condition)
                    <button type="button" wire:click="$set('condition', '')" class="chip hover:border-gold">{{ \App\Enums\Condition::tryFrom($condition)?->getLabel() }} <span aria-hidden="true">✕</span></button>
                @endif
                @if ($fuel)
                    <button type="button" wire:click="$set('fuel', '')" class="chip hover:border-gold">{{ \App\Enums\FuelType::tryFrom($fuel)?->getLabel() }} <span aria-hidden="true">✕</span></button>
                @endif
            </div>

            <div wire:loading.class="opacity-50" wire:target="search,brands,body,condition,fuel,minPrice,maxPrice,minYear,minHp,sort,includeSold,clearFilters,removeBrand,gotoPage,nextPage,previousPage" class="transition-opacity">
                @if ($vehicles->isEmpty())
                    <div class="card grid place-items-center gap-4 px-6 py-20 text-center">
                        <p class="font-display text-3xl tracking-wide text-white">No matches — yet</p>
                        <p class="max-w-md text-mist">Our inventory changes weekly. Loosen a filter, or tell us what you're after and we'll source it.</p>
                        <div class="flex gap-3">
                            <button type="button" wire:click="clearFilters" class="btn-outline">Clear filters</button>
                            <a href="{{ route('contact', ['topic' => 'sourcing']) }}" class="btn-gold">Find it for me</a>
                        </div>
                    </div>
                @else
                    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($vehicles as $vehicle)
                            <x-vehicle-card :vehicle="$vehicle" wire:key="vehicle-{{ $vehicle->id }}" />
                        @endforeach
                    </div>
                    <div class="mt-10">
                        {{ $vehicles->links() }}
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
