@php
    $docUrl = $link->show_documents ? fn ($doc) => route('passport.document', [$link, $doc]) : null;
    $verified = $vehicle->records->filter(fn ($r) => $r->evidence() === 'verified')->count();
@endphp

<x-layouts.site :title="$vehicle->title().' — vehicle passport'" robots="noindex">
    <section class="grain border-b border-line">
        <div class="container-x py-10">
            <div class="flex flex-wrap items-start justify-between gap-6">
                <div>
                    <p class="eyebrow">Vehicle passport · shared {{ $link->created_at->format('M j, Y') }}</p>
                    <h1 class="display mt-2 text-4xl sm:text-5xl">{{ $vehicle->title() }}</h1>
                    <p class="mt-1 text-lg text-ink-soft">{{ $vehicle->trim }}</p>
                    <p class="vin mt-3 text-sm">{{ $link->show_full_vin ? $vehicle->vin : $vehicle->maskedVin() }}
                        @if ($vehicle->vin_valid)<span class="stamp ml-2 border-verified text-verified">VIN valid</span>@endif</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($listing)
                        <a href="{{ route('listings.show', $listing) }}" class="btn-accent">For sale · {{ money($listing->price_cents) }}</a>
                    @endif
                    <a href="{{ route('passport.pdf', $link) }}" class="btn-secondary"><x-heroicon-m-arrow-down-tray class="size-4" /> PDF report</a>
                </div>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ([['Passport Score', $score['total'].'/100'], ['Odometer', miles($vehicle->current_mileage)], ['Owners', $vehicle->ownerCount()], ['Records', $vehicle->records->count()], ['Shop verified', $verified]] as [$label, $value])
                    <div class="rounded-2xl border border-line bg-surface p-4">
                        <p class="eyebrow">{{ $label }}</p>
                        <p class="num mt-1 text-xl font-semibold">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <div class="container-x grid gap-8 py-10 lg:grid-cols-[1fr_360px]">
        <div class="min-w-0 space-y-8">
            @if ($vehicle->photos->isNotEmpty())
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($vehicle->photos->take($vehicle->photos->count() < 3 ? 1 : 3) as $photo)
                        <x-car-photo :vehicle="$vehicle" :url="$photo->url()" :class="$vehicle->photos->count() < 3 ? 'col-span-3 aspect-[16/9] rounded-2xl' : 'aspect-[4/3] rounded-2xl'" />
                    @endforeach
                </div>
            @endif
            <section class="card card-pad">
                <x-passport.facts :vehicle="$vehicle" :full-vin="$link->show_full_vin" />
            </section>
            <x-passport.mileage :vehicle="$vehicle" :anomalies="$anomalies" :miles-per-year="$milesPerYear" />
            <section>
                <h2 class="panel-title mb-4">Service history</h2>
                <x-passport.timeline :records="$vehicle->records" :show-costs="$link->show_costs" :document-url="$docUrl" />
            </section>
        </div>
        <aside class="space-y-6">
            <x-passport.score :score="$score" />
            <x-passport.owners :vehicle="$vehicle" />
            <x-passport.recalls :vehicle="$vehicle" />
            <section class="rounded-2xl border border-line bg-paper-deep/60 p-5 text-sm text-ink-soft">
                <p class="font-semibold text-ink">Reading this passport</p>
                <ul class="mt-2 space-y-2">
                    <li><span class="stamp border-verified text-verified">Shop verified</span> — the shop that did the work confirmed it.</li>
                    <li><span class="badge-blue">Receipt</span> — an invoice is attached.</li>
                    <li><span class="badge-gray">Self-reported</span> — entered by the owner without proof.</li>
                </ul>
                <p class="mt-3">Always inspect a car in person. <a href="{{ route('how-it-works') }}" class="link">How passports work</a></p>
            </section>
        </aside>
    </div>
</x-layouts.site>
