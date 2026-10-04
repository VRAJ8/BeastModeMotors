<x-layouts.storefront>
    {{-- Hero --}}
    <section x-data="{ active: 0, count: {{ $hero->count() }}, init() { if (this.count > 1) setInterval(() => this.active = (this.active + 1) % this.count, 6000) } }"
             class="relative -mt-18 flex min-h-[92vh] items-end overflow-hidden pb-20 sm:items-center sm:pb-0">
        @foreach ($hero as $i => $car)
            <div x-show="active === {{ $i }}" x-transition:enter="transition-opacity duration-1000" x-transition:enter-start="opacity-0" x-transition:leave="transition-opacity duration-1000" x-transition:leave-end="opacity-0"
                 @if ($i > 0) x-cloak @endif class="absolute inset-0">
                <img src="{{ $car->cover_image }}" data-fallback="{{ asset(\App\Models\Vehicle::PLACEHOLDER_IMAGE) }}" alt="" class="h-full w-full animate-ken-burns object-cover" @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif>
            </div>
        @endforeach
        <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/80 to-ink/20"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-ink via-transparent to-ink/60"></div>

        <div class="container-x relative pt-32">
            <div class="max-w-2xl animate-fade-up">
                <p class="eyebrow mb-5">Miami · Exotic &amp; Performance</p>
                <h1 class="heading-display text-6xl leading-[0.9] sm:text-8xl lg:text-9xl">
                    Unleash your<br><span class="text-gold-gradient">beast mode</span>
                </h1>
                <p class="mt-6 max-w-lg text-lg text-silver/90">Hand-picked supercars, hypercars and luxury GTs. Every car inspected, every price transparent — book a test drive in thirty seconds.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('vehicles.index') }}" class="btn-gold" wire:navigate>Explore inventory</a>
                    <a href="{{ route('sell') }}" class="btn-outline">Value my car</a>
                </div>
            </div>

            @if ($hero->isNotEmpty())
                <div class="mt-14 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    @foreach ($hero as $i => $car)
                        <a x-show="active === {{ $i }}" @if ($i > 0) x-cloak @endif href="{{ route('vehicles.show', $car) }}" class="group block" wire:navigate>
                            <p class="text-xs tracking-widest text-mist uppercase">Now showing</p>
                            <p class="font-display text-3xl tracking-wide text-white group-hover:text-gold">{{ $car->title }} {{ $car->trim }}</p>
                            <p class="text-gold">{{ money($car->price) }} · {{ $car->horsepower }} hp · 0–60 in {{ $car->zero_to_sixty }}s</p>
                        </a>
                    @endforeach
                    <div class="flex gap-2" role="tablist" aria-label="Featured cars">
                        @foreach ($hero as $i => $car)
                            <button type="button" @click="active = {{ $i }}" :class="active === {{ $i }} ? 'w-10 bg-gold' : 'w-5 bg-white/30 hover:bg-white/60'" class="h-1.5 rounded-full transition-all" role="tab" :aria-selected="active === {{ $i }}" aria-label="Show {{ $car->title }}"></button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Stats --}}
    <section class="border-y border-white/5 bg-carbon">
        <dl class="container-x grid grid-cols-2 divide-white/5 py-10 text-center md:grid-cols-4 md:divide-x">
            @foreach ([
                ['value' => $stats['in_stock'], 'label' => 'Cars in stock'],
                ['value' => $stats['brands'], 'label' => 'Marques'],
                ['value' => number_format($stats['horsepower']), 'label' => 'Horsepower on the floor'],
                ['value' => number_format($stats['sold']).'+', 'label' => 'Happy owners'],
            ] as $stat)
                <div class="flex flex-col px-4 py-3">
                    <dt class="order-2 mt-1 text-xs tracking-widest text-mist uppercase">{{ $stat['label'] }}</dt>
                    <dd class="font-display text-5xl text-gold">{{ $stat['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Featured --}}
    <section class="container-x py-24">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <x-site.section-heading eyebrow="Hand-picked" title="Featured machines" class="mb-0" />
            <a href="{{ route('vehicles.index') }}" class="link-gold text-sm font-semibold tracking-wider uppercase" wire:navigate>View all inventory →</a>
        </div>
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($featured as $vehicle)
                <x-vehicle-card :vehicle="$vehicle" />
            @endforeach
        </div>
    </section>

    {{-- Body types --}}
    <section class="bg-carbon py-24">
        <div class="container-x">
            <x-site.section-heading eyebrow="Shop by style" title="Find your silhouette" align="center" />
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                @foreach ($bodyTypes as $body)
                    <a href="{{ route('vehicles.index', ['body' => $body['type']->value]) }}" class="card group flex flex-col items-center gap-2 px-4 py-8 text-center transition hover:border-gold/40" wire:navigate>
                        <span class="font-display text-2xl tracking-wide text-white group-hover:text-gold">{{ $body['type']->getLabel() }}</span>
                        <span class="text-xs text-mist">{{ $body['count'] }} in stock</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Brands --}}
    <section class="container-x py-24">
        <x-site.section-heading eyebrow="The marques" title="Legends under one roof" align="center">
            From Sant'Agata to Stuttgart, Woking to Molsheim — the world's most coveted badges, curated in Miami.
        </x-site.section-heading>
        <div class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-white/5 bg-white/5 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($brands as $brand)
                <a href="{{ route('brands.show', $brand) }}" class="group bg-ink px-4 py-8 text-center transition hover:bg-carbon">
                    <p class="font-display text-2xl tracking-wider text-white group-hover:text-gold">{{ $brand->name }}</p>
                    <p class="mt-1 text-xs text-mist">{{ $brand->vehicles_count }} available</p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Why us --}}
    <section class="relative overflow-hidden bg-carbon py-24">
        <div class="pointer-events-none absolute -top-40 -right-40 h-[32rem] w-[32rem] rounded-full bg-gold/10 blur-3xl"></div>
        <div class="container-x relative grid gap-16 lg:grid-cols-2 lg:items-center">
            <div>
                <x-site.section-heading eyebrow="The Beast Mode standard" title="Buying a supercar should feel like driving one">
                    No haggling theatre, no hidden fees. Just extraordinary cars and people who know them inside out.
                </x-site.section-heading>
                <a href="{{ route('about') }}" class="btn-outline">Our story</a>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['title' => '200-point inspection', 'body' => 'Every car is inspected by factory-trained technicians before it reaches the floor.'],
                    ['title' => 'Transparent pricing', 'body' => 'The price you see is the price you pay. Price drops are public and we\'ll alert you to them.'],
                    ['title' => 'Instant trade-in', 'body' => 'Get an indicative value online in seconds, then a firm offer after a 20-minute appraisal.'],
                    ['title' => 'Global delivery', 'body' => 'Enclosed transport to your door, anywhere in the US, and export assistance worldwide.'],
                ] as $item)
                    <div class="card p-6">
                        <div class="mb-4 h-1 w-10 rounded bg-gold"></div>
                        <h3 class="font-display text-2xl tracking-wide text-white">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-sm text-mist">{{ $item['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Testimonials --}}
    @if ($testimonials->isNotEmpty())
        <section class="container-x py-24">
            <x-site.section-heading eyebrow="Owners' circle" title="Don't take our word for it" align="center" />
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($testimonials as $t)
                    <figure class="card flex flex-col p-8">
                        <div class="text-gold" aria-label="{{ $t->rating }} out of 5 stars">{{ str_repeat('★', $t->rating) }}</div>
                        <blockquote class="mt-4 flex-1 text-silver">“{{ $t->quote }}”</blockquote>
                        <figcaption class="mt-6 border-t border-white/5 pt-4">
                            <p class="font-semibold text-white">{{ $t->name }}</p>
                            <p class="text-sm text-mist">{{ $t->title }}@if ($t->vehicle) · {{ $t->vehicle }}@endif</p>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Latest + CTA --}}
    <section class="container-x">
        <div class="relative overflow-hidden rounded-2xl border border-gold/20 bg-gradient-to-br from-graphite via-carbon to-ink p-10 sm:p-16">
            <div class="pointer-events-none absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-ember/20 blur-3xl"></div>
            <div class="relative grid gap-10 lg:grid-cols-2 lg:items-center">
                <div>
                    <p class="eyebrow mb-3">Ready when you are</p>
                    <h2 class="heading-display text-5xl sm:text-6xl">Your next drive is <span class="text-gold-gradient">one click</span> away</h2>
                    <p class="mt-4 max-w-md text-mist">Save cars to your garage, get price-drop alerts and manage test drives — all in one place.</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        @guest
                            <a href="{{ route('register') }}" class="btn-gold">Create free account</a>
                        @else
                            <a href="{{ route('garage') }}" class="btn-gold">Open my garage</a>
                        @endguest
                        <a href="{{ route('contact') }}" class="btn-outline">Talk to a specialist</a>
                    </div>
                </div>
                <ul class="space-y-3">
                    @foreach ($latest as $car)
                        <li>
                            <a href="{{ route('vehicles.show', $car) }}" class="group flex items-center gap-4 rounded-lg border border-white/5 bg-ink/50 p-3 transition hover:border-gold/30" wire:navigate>
                                <img src="{{ $car->cover_image }}" data-fallback="{{ asset(\App\Models\Vehicle::PLACEHOLDER_IMAGE) }}" alt="" loading="lazy" class="h-14 w-20 rounded object-cover">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-white group-hover:text-gold">{{ $car->title }} {{ $car->trim }}</p>
                                    <p class="text-xs text-mist">Just arrived · {{ number_format($car->mileage) }} mi</p>
                                </div>
                                <p class="font-semibold text-gold">{{ money($car->price) }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>
</x-layouts.storefront>
