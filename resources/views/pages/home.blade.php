@php
    $featured = \App\Models\Listing::public()->with('vehicle.photos')->orderByDesc('score')->latest('published_at')->take(3)->get();
@endphp

<x-layouts.site>
    {{-- Hero --}}
    <section class="grain relative overflow-hidden border-b border-line">
        <div class="container-x grid items-center gap-12 py-16 lg:grid-cols-[1.1fr_1fr] lg:py-24">
            <div class="animate-rise">
                <p class="eyebrow">The car passport</p>
                <h1 class="display mt-4 text-5xl leading-[1.02] sm:text-6xl lg:text-7xl">The history of a car should belong to <span class="text-accent">the car.</span></h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-ink-soft">Log every service as it happens. Have the shop confirm it with one click. When you sell, hand the whole verified history to the next owner — and get paid for the care you put in.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn-primary px-6 py-3 text-base">Start a free passport</a>
                    <a href="{{ route('marketplace') }}" class="btn-secondary px-6 py-3 text-base">Browse cars with history</a>
                </div>
                <p class="mt-6 flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted">
                    <span class="flex items-center gap-1.5"><x-heroicon-s-check-circle class="size-4 text-verified" /> Free for owners</span>
                    <span class="flex items-center gap-1.5"><x-heroicon-s-check-circle class="size-4 text-verified" /> Shops verify without an account</span>
                    <span class="flex items-center gap-1.5"><x-heroicon-s-check-circle class="size-4 text-verified" /> We never touch your money</span>
                </p>
            </div>

            {{-- Illustrative passport card --}}
            <div class="relative mx-auto w-full max-w-md animate-rise [animation-delay:120ms]" aria-hidden="true">
                <div class="absolute -inset-4 -rotate-3 rounded-[2rem] bg-accent/10"></div>
                <div class="card relative rotate-1 p-6 shadow-2xl shadow-ink/10">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="eyebrow">Vehicle passport</p>
                            <p class="display mt-1 text-2xl">2019 Porsche 911</p>
                            <p class="vin mt-1 text-xs text-muted">WP0AB2A9XKS1••••••</p>
                        </div>
                        <x-score-ring :score="91" size="md" />
                    </div>
                    <div class="divider my-5"></div>
                    <ol class="space-y-3 text-sm">
                        @foreach ([
                            ['May 2026', '40,000-mile service', 'verified', 'Eastside Euro'],
                            ['Nov 2025', 'Front brake pads & discs', 'verified', 'Porsche Coral Gables'],
                            ['Jun 2025', 'Michelin Pilot Sport 4S ×4', 'documented', 'Tire Kingdom'],
                            ['Jan 2025', 'Ceramic coating', 'self', 'Owner'],
                        ] as [$date, $title, $proof, $by])
                            <li class="flex items-center gap-3">
                                <span class="num w-16 shrink-0 text-xs text-muted">{{ $date }}</span>
                                <span class="min-w-0 flex-1"><span class="block truncate font-medium">{{ $title }}</span><span class="text-xs text-muted">{{ $by }}</span></span>
                                @if ($proof === 'verified')
                                    <span class="stamp border-verified text-verified">Verified</span>
                                @elseif ($proof === 'documented')
                                    <span class="badge-blue">Receipt</span>
                                @else
                                    <span class="badge-gray">Self</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                    <div class="mt-5 grid grid-cols-3 gap-2 rounded-xl bg-paper p-3 text-center">
                        <div><p class="num font-semibold">38,412</p><p class="text-[10px] text-muted uppercase">miles</p></div>
                        <div><p class="num font-semibold">2</p><p class="text-[10px] text-muted uppercase">owners</p></div>
                        <div><p class="num font-semibold">0</p><p class="text-[10px] text-muted uppercase">open recalls</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- The problem --}}
    <section class="container-x py-20">
        <div class="grid gap-10 lg:grid-cols-[1fr_1.4fr]">
            <div>
                <p class="eyebrow">The problem</p>
                <h2 class="display mt-3 text-4xl">Private car sales run on trust nobody can check.</h2>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                @foreach ([
                    ['heroicon-o-folder-minus', 'Good owners can\'t prove it', 'Receipts end up in glove boxes and inboxes. A well-kept car sells for the same as a neglected one because the seller can\'t show the difference.'],
                    ['heroicon-o-eye-slash', 'Buyers can\'t verify anything', 'Report services list what\'s been reported to them. Anything done at an independent shop or at home is invisible.'],
                    ['heroicon-o-arrow-trending-down', 'Odometer fraud is still common', 'Rollbacks are easier than ever on digital clusters, and a single reading every few years can\'t catch them.'],
                    ['heroicon-o-exclamation-triangle', 'Scams follow the money', 'Fake escrow, "deployed" sellers and gift-card payments target exactly the people who skip dealers.'],
                ] as [$icon, $title, $text])
                    <div class="card card-pad">
                        <x-dynamic-component :component="$icon" class="size-6 text-accent" />
                        <p class="mt-4 font-semibold">{{ $title }}</p>
                        <p class="mt-1 text-sm leading-relaxed text-ink-soft">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="border-y border-line bg-surface">
        <div class="container-x py-20">
            <p class="eyebrow text-center">How it works</p>
            <h2 class="display mx-auto mt-3 max-w-2xl text-center text-4xl">A history that's written as it happens — and checked by the people who did the work.</h2>
            <ol class="mt-14 grid gap-8 md:grid-cols-4">
                @foreach ([
                    ['Decode', 'Enter the VIN. We validate the check digit, decode the car from NHTSA, pull open safety recalls and set up a maintenance plan.'],
                    ['Log', 'Add services, repairs and upgrades with the receipt. Every record is also an odometer reading, so rollbacks stand out.'],
                    ['Verify', 'One tap emails the shop a signed link. They confirm or dispute the record — no account needed. Confirmed records get a stamp.'],
                    ['Hand over', 'Sell through a deal room with offers, inspection and a handover checklist. When you both confirm, the passport moves to the buyer.'],
                ] as $i => [$title, $text])
                    <li>
                        <span class="num grid size-10 place-items-center rounded-full bg-ink text-sm font-semibold text-white">{{ $i + 1 }}</span>
                        <p class="display mt-4 text-xl">{{ $title }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
            <div class="mt-12 text-center"><a href="{{ route('how-it-works') }}" class="link">Read the details, including how the Passport Score works</a></div>
        </div>
    </section>

    {{-- Features --}}
    <section class="container-x py-20">
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="card card-pad lg:row-span-2">
                <p class="eyebrow">For owners</p>
                <h3 class="display mt-2 text-2xl">Your car's paperwork, finally in one place.</h3>
                <ul class="mt-6 space-y-4 text-sm">
                    @foreach ([
                        'Maintenance reminders by mileage or time, whichever comes first',
                        'Expiry alerts for registration, insurance and warranties',
                        'Weekly recall checks against NHTSA, with email alerts',
                        'Running costs, cost per mile and real-world fuel economy',
                        'Share links with per-link privacy, view counts and expiry',
                        'A printable PDF report and a QR "for sale" window sign',
                    ] as $item)
                        <li class="flex gap-3"><x-heroicon-s-check-circle class="size-5 shrink-0 text-verified" /> {{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="card card-pad">
                <p class="eyebrow">For buyers</p>
                <h3 class="display mt-2 text-xl">Sort cars by how well they're documented.</h3>
                <p class="mt-2 text-sm text-ink-soft">Every listing shows its Passport Score, odometer history, owners and recalls. Filter for 85+ and skip the guesswork.</p>
            </div>
            <div class="card card-pad">
                <p class="eyebrow">For shops</p>
                <h3 class="display mt-2 text-xl">Confirm your work in one click.</h3>
                <p class="mt-2 text-sm text-ink-soft">No logins, no software. A signed link shows the record; you confirm or flag it. Your name goes on the stamp.</p>
            </div>
            <div class="rounded-2xl bg-ink p-6 text-white lg:col-span-2">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                    <x-heroicon-o-shield-check class="size-12 shrink-0 text-accent" />
                    <div>
                        <p class="font-display text-xl font-semibold">Scam shield in every deal room</p>
                        <p class="mt-1 text-sm text-white/70">Messages mentioning gift cards, wire transfers, fake escrow, "shipping agents" or verification codes are flagged to the other person with plain-English advice. We never hold money, so there's nothing to impersonate.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Featured listings --}}
    @if ($featured->isNotEmpty())
        <section class="border-t border-line bg-paper-deep/50">
            <div class="container-x py-20">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">On the marketplace</p>
                        <h2 class="display mt-2 text-3xl">Best-documented cars for sale</h2>
                    </div>
                    <a href="{{ route('marketplace') }}" class="btn-secondary">See all cars</a>
                </div>
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featured as $listing)
                        <x-listing-card :listing="$listing" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- FAQ --}}
    <section class="container-x max-w-3xl py-20">
        <h2 class="display text-center text-3xl">Questions</h2>
        <div class="mt-10 divide-y divide-line border-y border-line">
            @foreach ([
                ['Isn\'t this what vehicle history reports do?', 'Those reports list what insurers, auctions and some dealers report to them. They miss most independent shops and all home maintenance, and they can\'t tell you whether the oil was changed on time. A passport is written by the owner as it happens, with receipts and shop confirmations — and it complements a history report rather than replacing it.'],
                ['What stops an owner making records up?', 'Every record shows its evidence: shop-verified, receipt attached, or self-reported. Records logged long after the fact are labelled. Odometer readings that go backwards are flagged. The Passport Score rewards proof, so an honest short history beats a long unverifiable one.'],
                ['What happens to my data when I sell?', 'Service records, receipts, inspection reports, photos and odometer history move with the car. Your running costs, personal documents (title scans, insurance, registration) and share links stay with you or are deleted. The buyer sees "Owner 1", not your name.'],
                ['Do you handle payment?', 'No — and that\'s deliberate. Fake escrow services are one of the most common car-sale scams. We give you a deal room, a checklist and a bill of sale; you pay at a bank or in person.'],
                ['What does it cost?', 'Keeping a passport is free. The marketplace is free while we\'re in early access.'],
            ] as [$q, $a])
                <details class="group py-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">{{ $q }}<x-heroicon-m-plus class="size-5 shrink-0 text-muted transition group-open:rotate-45" /></summary>
                    <p class="mt-3 text-sm leading-relaxed text-ink-soft">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </section>

    <section class="container-x pb-4">
        <div class="relative overflow-hidden rounded-3xl bg-ink px-8 py-14 text-center text-white sm:px-16">
            <div class="grain absolute inset-0 opacity-20"></div>
            <h2 class="relative font-display text-4xl font-semibold tracking-tight">Start the record today. Your next buyer will thank you.</h2>
            <div class="relative mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('register') }}" class="btn-accent px-6 py-3 text-base">Create a free passport</a>
                <a href="{{ route('vin-check') }}" class="btn border border-white/20 px-6 py-3 text-base text-white hover:bg-white/10">Check a VIN first</a>
            </div>
        </div>
    </section>
</x-layouts.site>
