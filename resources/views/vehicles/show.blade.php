@php
    $finance = config('dealership.finance');
@endphp

<x-layouts.storefront :title="$vehicle->title.' '.$vehicle->trim" :description="str($vehicle->description)->limit(155)" :image="$vehicle->cover_image">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($vehicle->toSchemaOrg(), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    <div class="container-x pt-6">
        <nav class="text-sm text-mist" aria-label="Breadcrumb">
            <ol class="flex flex-wrap items-center gap-2">
                <li><a href="{{ route('vehicles.index') }}" class="hover:text-gold" wire:navigate>Inventory</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('brands.show', $vehicle->brand) }}" class="hover:text-gold">{{ $vehicle->brand->name }}</a></li>
                <li aria-hidden="true">/</li>
                <li class="text-silver" aria-current="page">{{ $vehicle->model }} {{ $vehicle->trim }}</li>
            </ol>
        </nav>
    </div>

    <div class="container-x grid gap-10 py-8 lg:grid-cols-[1fr_24rem] xl:grid-cols-[1fr_26rem]">
        {{-- Gallery --}}
        <section x-data="{ current: 0, images: @js($vehicle->image_urls), lightbox: false }" @keydown.escape.window="lightbox = false" aria-label="Photos">
            <div class="relative aspect-[16/10] overflow-hidden rounded-xl bg-graphite">
                <template x-for="(src, i) in images" :key="src + i">
                    <img x-show="current === i" x-transition.opacity.duration.500ms :src="src" data-fallback="{{ asset(\App\Models\Vehicle::PLACEHOLDER_IMAGE) }}"
                         alt="{{ $vehicle->title }}" class="absolute inset-0 h-full w-full cursor-zoom-in object-cover {{ $vehicle->isAvailable() ? '' : 'grayscale' }}" @click="lightbox = true">
                </template>
                @unless ($vehicle->isAvailable())
                    <div class="absolute top-4 left-4 badge bg-ember px-3 py-1 text-sm text-white">{{ $vehicle->status->getLabel() }}</div>
                @endunless
                <template x-if="images.length > 1">
                    <div>
                        <button type="button" @click="current = (current - 1 + images.length) % images.length" class="absolute top-1/2 left-3 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full bg-black/60 text-white hover:bg-black/80" aria-label="Previous photo">‹</button>
                        <button type="button" @click="current = (current + 1) % images.length" class="absolute top-1/2 right-3 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full bg-black/60 text-white hover:bg-black/80" aria-label="Next photo">›</button>
                        <span class="absolute right-3 bottom-3 rounded bg-black/60 px-2 py-1 text-xs text-white" x-text="(current + 1) + ' / ' + images.length"></span>
                    </div>
                </template>
            </div>
            <div class="mt-3 grid grid-cols-5 gap-2" x-show="images.length > 1">
                <template x-for="(src, i) in images" :key="'t' + src + i">
                    <button type="button" @click="current = i" :class="current === i ? 'ring-2 ring-gold' : 'opacity-60 hover:opacity-100'" class="aspect-[16/10] overflow-hidden rounded-md transition">
                        <img :src="src" data-fallback="{{ asset(\App\Models\Vehicle::PLACEHOLDER_IMAGE) }}" alt="" class="h-full w-full object-cover">
                    </button>
                </template>
            </div>

            <div x-show="lightbox" x-cloak x-transition.opacity class="fixed inset-0 z-[70] grid place-items-center bg-black/95 p-4" @click.self="lightbox = false" role="dialog" aria-modal="true" aria-label="Photo viewer">
                <button type="button" @click="lightbox = false" class="absolute top-4 right-4 text-3xl text-white" aria-label="Close">✕</button>
                <img :src="images[current]" alt="{{ $vehicle->title }}" class="max-h-[90vh] max-w-full rounded-lg object-contain">
            </div>

            {{-- Overview --}}
            <div class="mt-12 space-y-12">
                <div>
                    <h2 class="heading-display mb-4 text-3xl">Overview</h2>
                    <p class="leading-relaxed text-silver">{{ $vehicle->description }}</p>
                </div>

                @if ($vehicle->horsepower)
                    <div>
                        <h2 class="heading-display mb-6 text-3xl">Performance</h2>
                        <div class="grid gap-5 sm:grid-cols-2">
                            @foreach ([
                                ['label' => 'Horsepower', 'value' => $vehicle->horsepower, 'unit' => 'hp', 'pct' => $vehicle->horsepower / 1500],
                                ['label' => 'Torque', 'value' => $vehicle->torque, 'unit' => 'lb-ft', 'pct' => $vehicle->torque / 1200],
                                ['label' => '0–60 mph', 'value' => $vehicle->zero_to_sixty, 'unit' => 's', 'pct' => $vehicle->zero_to_sixty ? (6 - $vehicle->zero_to_sixty) / 4 : 0],
                                ['label' => 'Top speed', 'value' => $vehicle->top_speed, 'unit' => 'mph', 'pct' => $vehicle->top_speed / 270],
                            ] as $metric)
                                <div>
                                    <div class="mb-2 flex items-baseline justify-between">
                                        <span class="text-sm text-mist">{{ $metric['label'] }}</span>
                                        <span class="font-display text-2xl text-white">{{ $metric['value'] ?? '—' }} <span class="text-sm text-mist">{{ $metric['unit'] }}</span></span>
                                    </div>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-steel">
                                        <div class="h-full rounded-full bg-gradient-to-r from-gold-dark to-gold-light" style="width: {{ max(4, min(100, round($metric['pct'] * 100))) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div>
                    <h2 class="heading-display mb-6 text-3xl">Specifications</h2>
                    <dl class="grid grid-cols-2 gap-3 md:grid-cols-3">
                        <x-spec label="Year" :value="$vehicle->year" />
                        <x-spec label="Condition" :value="$vehicle->condition->getLabel()" />
                        <x-spec label="Mileage" :value="number_format($vehicle->mileage).' mi'" />
                        <x-spec label="Body" :value="$vehicle->body_type->getLabel()" />
                        <x-spec label="Engine" :value="$vehicle->engine" />
                        <x-spec label="Powertrain" :value="$vehicle->fuel_type->getLabel()" />
                        <x-spec label="Transmission" :value="$vehicle->transmission->getLabel()" />
                        <x-spec label="Drivetrain" :value="$vehicle->drivetrain->getLabel()" />
                        <x-spec label="Exterior" :value="$vehicle->exterior_color" />
                        <x-spec label="Interior" :value="$vehicle->interior_color" />
                        <x-spec label="VIN" :value="$vehicle->vin" />
                        <x-spec label="Stock no." :value="'BMM-'.str_pad($vehicle->id, 4, '0', STR_PAD_LEFT)" />
                    </dl>
                </div>

                @if ($vehicle->features)
                    <div>
                        <h2 class="heading-display mb-6 text-3xl">Highlights</h2>
                        <ul class="grid gap-3 sm:grid-cols-2">
                            @foreach ($vehicle->features as $feature)
                                <li class="flex items-start gap-3 text-silver"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-gold"></span>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Finance calculator --}}
                <div x-data="{
                        price: {{ $vehicle->price }},
                        deposit: {{ (int) round($vehicle->price * $finance['deposit_percent'] / 100, -3) }},
                        apr: {{ $finance['apr'] }},
                        term: {{ $finance['term_months'] }},
                        get principal() { return Math.max(0, this.price - this.deposit) },
                        get monthly() {
                            const r = this.apr / 100 / 12;
                            if (this.principal === 0) return 0;
                            return r === 0 ? this.principal / this.term : this.principal * r / (1 - Math.pow(1 + r, -this.term));
                        },
                        get totalInterest() { return this.monthly * this.term - this.principal },
                        fmt(n) { return '$' + Math.round(n).toLocaleString('en-US') },
                     }" class="card p-6 sm:p-8" id="finance">
                    <h2 class="heading-display text-3xl">Finance calculator</h2>
                    <p class="mt-1 text-sm text-mist">Representative example. Final rates depend on credit approval.</p>
                    <div class="mt-6 grid gap-8 md:grid-cols-2">
                        <div class="space-y-6">
                            <div>
                                <div class="flex justify-between text-sm"><label for="deposit" class="text-mist">Deposit</label><span class="font-semibold text-white" x-text="fmt(deposit)"></span></div>
                                <input id="deposit" type="range" min="0" :max="price" step="1000" x-model.number="deposit" class="mt-2 w-full accent-[#d4a857]">
                            </div>
                            <div>
                                <div class="flex justify-between text-sm"><label for="term" class="text-mist">Term</label><span class="font-semibold text-white" x-text="term + ' months'"></span></div>
                                <input id="term" type="range" min="12" max="84" step="12" x-model.number="term" class="mt-2 w-full accent-[#d4a857]">
                            </div>
                            <div>
                                <div class="flex justify-between text-sm"><label for="apr" class="text-mist">APR</label><span class="font-semibold text-white" x-text="apr.toFixed(1) + '%'"></span></div>
                                <input id="apr" type="range" min="0" max="15" step="0.1" x-model.number="apr" class="mt-2 w-full accent-[#d4a857]">
                            </div>
                        </div>
                        <div class="flex flex-col justify-center rounded-xl border border-gold/20 bg-ink p-6 text-center">
                            <p class="eyebrow">Estimated monthly</p>
                            <p class="mt-2 font-display text-6xl text-white" x-text="fmt(monthly)"></p>
                            <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
                                <dt class="text-mist">Amount financed</dt><dd class="text-right text-white" x-text="fmt(principal)"></dd>
                                <dt class="text-mist">Total interest</dt><dd class="text-right text-white" x-text="fmt(totalInterest)"></dd>
                            </dl>
                            <a href="{{ route('contact', ['topic' => 'finance']) }}" class="btn-outline mt-6">Apply for finance</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Sidebar --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="card p-6">
                <p class="text-xs font-semibold tracking-widest text-mist uppercase">{{ $vehicle->year }} · {{ $vehicle->brand->name }} · {{ $vehicle->condition->getLabel() }}</p>
                <h1 class="mt-1 font-display text-5xl leading-none tracking-wide text-white">{{ $vehicle->model }} <span class="text-gold">{{ $vehicle->trim }}</span></h1>

                <div class="mt-5 flex items-end justify-between gap-4">
                    <div>
                        @if ($vehicle->has_price_drop)
                            <p class="text-sm text-mist"><span class="line-through">{{ money($vehicle->previous_price) }}</span> <span class="badge ml-1 bg-emerald-600 text-white">Save {{ money($vehicle->previous_price - $vehicle->price) }}</span></p>
                        @endif
                        <p class="text-4xl font-bold text-white">{{ money($vehicle->price) }}</p>
                        <a href="#finance" class="text-sm text-gold hover:underline">or est. {{ money(\App\Support\Finance::monthlyPayment($vehicle->price)) }}/mo</a>
                    </div>
                    <livewire:vehicle-actions :vehicle="$vehicle" />
                </div>

                <dl class="mt-6 grid grid-cols-3 gap-2 border-y border-white/5 py-4 text-center">
                    <div><dt class="text-[10px] tracking-widest text-mist uppercase">Power</dt><dd class="font-display text-2xl text-white">{{ $vehicle->horsepower ?? '—' }}<span class="text-xs text-mist"> hp</span></dd></div>
                    <div><dt class="text-[10px] tracking-widest text-mist uppercase">0–60</dt><dd class="font-display text-2xl text-white">{{ $vehicle->zero_to_sixty ?? '—' }}<span class="text-xs text-mist"> s</span></dd></div>
                    <div><dt class="text-[10px] tracking-widest text-mist uppercase">Miles</dt><dd class="font-display text-2xl text-white">{{ number_format($vehicle->mileage) }}</dd></div>
                </dl>

                <div x-data="{ tab: 'drive' }" class="mt-6">
                    <div class="mb-5 grid grid-cols-2 rounded-lg bg-ink p-1" role="tablist">
                        <button type="button" role="tab" :aria-selected="tab === 'drive'" @click="tab = 'drive'" :class="tab === 'drive' ? 'bg-graphite text-white' : 'text-mist'" class="rounded-md py-2 text-sm font-semibold transition">Test drive</button>
                        <button type="button" role="tab" :aria-selected="tab === 'offer'" @click="tab = 'offer'" :class="tab === 'offer' ? 'bg-graphite text-white' : 'text-mist'" class="rounded-md py-2 text-sm font-semibold transition">Make an offer</button>
                    </div>
                    <div x-show="tab === 'drive'" role="tabpanel"><livewire:book-test-drive :vehicle="$vehicle" /></div>
                    <div x-show="tab === 'offer'" x-cloak role="tabpanel">
                        @if ($vehicle->isAvailable())
                            <livewire:make-offer :vehicle="$vehicle" />
                        @else
                            <p class="text-mist">This car is no longer accepting offers.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between rounded-xl border border-white/5 px-5 py-4 text-sm">
                <span class="text-mist">{{ number_format($vehicle->views) }} views · listed {{ $vehicle->published_at->diffForHumans() }}</span>
                <a href="tel:{{ preg_replace('/[^+\d]/', '', config('dealership.phone')) }}" class="font-semibold text-gold">Call us</a>
            </div>
        </aside>
    </div>

    @if ($similar->isNotEmpty())
        <section class="container-x pt-12">
            <x-site.section-heading eyebrow="You might also like" title="Similar cars" />
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($similar as $car)
                    <x-vehicle-card :vehicle="$car" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.storefront>
