@php
    $photos = $vehicle->photos;
    $docUrl = $listing->shareLink?->isActive() && $listing->shareLink->show_documents
        ? fn ($doc) => route('passport.document', [$listing->shareLink, $doc])
        : null;
    $verified = $vehicle->records->filter(fn ($r) => $r->evidence() === 'verified')->count();
@endphp

<x-layouts.site :title="$vehicle->title().' for sale in '.$listing->location()" :description="$vehicle->fullTitle().' · '.miles($listing->mileage).' · Passport Score '.$score['total'].'/100 · '.$vehicle->records->count().' service records.'" :image="$photos->first()?->url()">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org', '@type' => 'Car',
            'name' => $vehicle->fullTitle(), 'brand' => ['@type' => 'Brand', 'name' => $vehicle->make], 'model' => $vehicle->model,
            'vehicleModelDate' => (string) $vehicle->year, 'vehicleIdentificationNumber' => $vehicle->vin,
            'mileageFromOdometer' => ['@type' => 'QuantitativeValue', 'value' => $listing->mileage, 'unitCode' => 'SMI'],
            'image' => $photos->map->url()->values(), 'description' => str($listing->description)->limit(300)->toString(),
            'offers' => ['@type' => 'Offer', 'price' => $listing->price_cents / 100, 'priceCurrency' => 'USD', 'availability' => 'https://schema.org/InStock'],
        ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    </x-slot:head>

    <div class="container-x py-6">
        <a href="{{ route('marketplace') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-heroicon-m-arrow-left class="size-4" /> All cars</a>

        @if ($isSeller)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-ink px-5 py-3 text-sm text-white">
                <span>This is your listing ({{ $listing->status->getLabel() }}) · {{ $listing->views }} views</span>
                <a href="{{ route('vehicles.sell', $vehicle) }}" class="font-semibold underline">Manage listing</a>
            </div>
        @endif

        <div class="mt-5 grid gap-8 lg:grid-cols-[1fr_380px]">
            <div class="min-w-0 space-y-8">
                {{-- Gallery --}}
                <div x-data="{ i: 0, n: {{ max(1, $photos->count()) }} }" class="space-y-3">
                    <div class="relative overflow-hidden rounded-3xl">
                        @if ($photos->isEmpty())
                            <x-car-photo :vehicle="$vehicle" class="aspect-[16/10]" />
                        @else
                            @foreach ($photos as $k => $photo)
                                <x-car-photo x-show="i === {{ $k }}" :vehicle="$vehicle" :url="$photo->url()" class="aspect-[16/10]" />
                            @endforeach
                            @if ($photos->count() > 1)
                                <button @click="i = (i - 1 + n) % n" class="absolute top-1/2 left-3 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 shadow" aria-label="Previous photo"><x-heroicon-m-chevron-left class="size-5" /></button>
                                <button @click="i = (i + 1) % n" class="absolute top-1/2 right-3 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 shadow" aria-label="Next photo"><x-heroicon-m-chevron-right class="size-5" /></button>
                                <span class="num absolute right-3 bottom-3 rounded-full bg-ink/80 px-2.5 py-1 text-xs text-white"><span x-text="i + 1"></span>/{{ $photos->count() }}</span>
                            @endif
                        @endif
                    </div>
                    @if ($photos->count() > 1)
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            @foreach ($photos as $k => $photo)
                                <button @click="i = {{ $k }}" :class="i === {{ $k }} ? 'ring-2 ring-ink' : 'opacity-70'" class="h-16 w-24 shrink-0 overflow-hidden rounded-xl">
                                    <img src="{{ $photo->url() }}" alt="" class="size-full object-cover" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <h1 class="display text-3xl sm:text-4xl">{{ $vehicle->title() }}</h1>
                    <p class="mt-1 text-lg text-ink-soft">{{ $vehicle->trim }}</p>
                    <div class="mt-4 flex items-center justify-between gap-4 rounded-2xl border border-line bg-surface p-4 lg:hidden">
                        <div>
                            <p class="num text-2xl font-semibold">{{ money($listing->price_cents) }}</p>
                            <p class="text-xs text-muted">{{ $listing->location() }} · Passport {{ $score['total'] }}/100</p>
                        </div>
                        <a href="#contact" class="btn-accent">{{ $isSeller ? 'Manage' : 'Contact seller' }}</a>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ([['Odometer', miles($listing->mileage)], ['Owners', $vehicle->ownerCount()], ['Records', $vehicle->records->count()], ['Shop verified', $verified]] as [$label, $value])
                            <div class="rounded-2xl border border-line bg-surface p-4">
                                <p class="eyebrow">{{ $label }}</p>
                                <p class="num mt-1 text-xl font-semibold">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <section>
                    <h2 class="panel-title mb-3">From the seller</h2>
                    <div class="card card-pad text-[15px] leading-relaxed whitespace-pre-line text-ink-soft">{{ $listing->description }}</div>
                </section>

                <section class="card card-pad">
                    <h2 class="panel-title mb-5">The car</h2>
                    <x-passport.facts :vehicle="$vehicle" :full-vin="$listing->shareLink?->show_full_vin ?? true" />
                </section>

                <x-passport.mileage :vehicle="$vehicle" :anomalies="$anomalies" :miles-per-year="$milesPerYear" />

                <section>
                    <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
                        <h2 class="panel-title">Service history</h2>
                        @if ($listing->shareLink?->isActive())
                            <a href="{{ route('passport.pdf', $listing->shareLink) }}" class="link text-sm">Download as PDF</a>
                        @endif
                    </div>
                    <x-passport.timeline :records="$vehicle->records" :document-url="$docUrl" />
                </section>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
                <section id="contact" class="card card-pad scroll-mt-20">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="num text-3xl font-semibold">{{ money($listing->price_cents) }}</p>
                            <p class="mt-1 flex items-center gap-1 text-sm text-muted"><x-heroicon-m-map-pin class="size-4" /> {{ $listing->location() }}</p>
                        </div>
                        @if ($listing->status === \App\Enums\ListingStatus::Pending)
                            <span class="badge-amber">Sale agreed</span>
                        @endif
                    </div>
                    <div class="divider my-5"></div>
                    @if ($isSeller)
                        <a href="{{ route('vehicles.sell', $vehicle) }}" class="btn-primary w-full">Manage listing</a>
                    @elseif ($existingDeal)
                        <a href="{{ route('deals.show', $existingDeal) }}" class="btn-accent w-full">Continue in your deal room</a>
                    @else
                        <livewire:listing-actions :listing="$listing" />
                    @endif
                    <div class="mt-5 flex items-center gap-3 border-t border-line pt-5">
                        <span class="grid size-10 place-items-center rounded-full bg-paper-deep text-sm font-bold">{{ $listing->seller->initials() }}</span>
                        <div class="text-sm">
                            <p class="font-semibold">{{ $listing->seller->publicName() }}</p>
                            <p class="text-xs text-muted">Member since {{ $listing->seller->created_at->format('M Y') }} · {{ $listing->seller->completedSalesCount() }} completed {{ str('sale')->plural($listing->seller->completedSalesCount()) }}</p>
                        </div>
                    </div>
                </section>

                <x-passport.score :score="$score" />
                <x-passport.owners :vehicle="$vehicle" />
                <x-passport.recalls :vehicle="$vehicle" />

                <section class="rounded-2xl border border-line bg-paper-deep/60 p-5 text-sm">
                    <p class="font-semibold">Buying safely</p>
                    <ul class="mt-2 space-y-1.5 text-ink-soft">
                        <li>• See the car in person and match the VIN to the title.</li>
                        <li>• Get an independent inspection before paying.</li>
                        <li>• Never pay with gift cards, crypto or wire transfers.</li>
                    </ul>
                    <a href="{{ route('safety') }}" class="link mt-3 inline-block">Full safety guide</a>
                </section>
            </aside>
        </div>

        @if ($similar->isNotEmpty())
            <section class="mt-16">
                <h2 class="panel-title mb-5">More {{ $vehicle->make }}s with a passport</h2>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($similar as $other)
                        <x-listing-card :listing="$other" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.site>
