<x-layouts.site title="How it works" description="How Beast Mode Motors car passports work: VIN decoding, shop-verified service records, odometer integrity checks, the Passport Score and ownership transfer.">
    <section class="grain border-b border-line">
        <div class="container-x max-w-4xl py-16">
            <p class="eyebrow">How it works</p>
            <h1 class="display mt-3 text-4xl sm:text-5xl">Proof, not promises.</h1>
            <p class="mt-4 max-w-2xl text-lg text-ink-soft">A passport is a running record of a car's life, kept by its owners and confirmed by the people who work on it. Here's exactly what it contains and how it's judged.</p>
        </div>
    </section>

    <div class="container-x max-w-4xl space-y-16 py-16">
        @foreach ([
            ['01', 'Identity', 'When you add a car, we validate the VIN\'s check digit — the 9th character is a checksum of the other 16, so typos and many forged VINs fail. We decode the specification from NHTSA\'s vPIC database and pull the safety recalls published for that model year. If NHTSA is unreachable, an offline decoder still identifies the maker, country and model year.'],
            ['02', 'Records with evidence', 'Every service, repair, tire change or modification is a record with a date, odometer reading, who did it and what it cost. Each record carries one of four evidence levels: shop verified, receipt attached, self-reported, or disputed. Records entered more than 30 days after the work are labelled "logged later" — honest, but weaker evidence.'],
            ['03', 'Shop verification', 'Owners can ask the shop that did the work to confirm a record. The shop receives a signed link that expires in 14 days, sees the record and the car\'s VIN, and confirms or disputes it — no account needed. A confirmed record is locked: its date, mileage, cost and description can\'t be edited afterwards, so the confirmation stays meaningful.'],
            ['04', 'Odometer integrity', 'Every record doubles as an odometer reading, alongside readings at purchase, sale and any time the owner updates it. If a reading is ever lower than an earlier one, it\'s flagged in red on the passport — the pattern a rollback leaves behind.'],
            ['05', 'Ownership transfer', 'When a car sells through a deal room and both people confirm the handover, the passport moves to the buyer\'s garage. Records, receipts, inspection reports, photos and odometer history go with the car. The seller\'s running costs, personal documents and share links do not. Previous owners appear as "Owner 1", "Owner 2" — never by name.'],
        ] as [$n, $title, $text])
            <section class="grid gap-4 sm:grid-cols-[120px_1fr]">
                <p class="num text-5xl font-semibold text-line-strong">{{ $n }}</p>
                <div>
                    <h2 class="display text-2xl">{{ $title }}</h2>
                    <p class="mt-3 leading-relaxed text-ink-soft">{{ $text }}</p>
                </div>
            </section>
        @endforeach

        <section id="score" class="card card-pad scroll-mt-24 sm:p-10">
            <p class="eyebrow">The Passport Score</p>
            <h2 class="display mt-2 text-3xl">0–100, and it shows its working.</h2>
            <p class="mt-3 text-ink-soft">The score measures how well a car's history is <em>evidenced</em>, not how much was spent on it. Every passport shows the breakdown and what would improve it.</p>
            <table class="mt-8 w-full text-sm">
                <thead><tr class="border-b border-line text-left"><th class="eyebrow py-2">Component</th><th class="eyebrow py-2">Points</th><th class="eyebrow py-2">How it's measured</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @foreach ([
                        ['Identity', 15, 'VIN passes the check-digit test (10) and the car has photos (5).'],
                        ['History coverage', 25, 'Share of the car\'s documented years (up to the last 10) with at least one record.'],
                        ['Quality of evidence', 30, 'Average evidence per record: shop verified 100%, receipt 70%, self-reported 30%, disputed 0%. Records logged later count at 60% unless verified.'],
                        ['Odometer integrity', 15, 'No reading ever goes backwards (10) and there\'s a reading from the last six months (5).'],
                        ['Upkeep & recalls', 15, 'No overdue maintenance (8, minus 3 per overdue item) and no open safety recalls (7).'],
                    ] as [$label, $points, $how])
                        <tr><td class="py-3 pr-4 font-medium">{{ $label }}</td><td class="num py-3 pr-4">{{ $points }}</td><td class="py-3 text-ink-soft">{{ $how }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-8 grid grid-cols-2 gap-3 text-center text-sm sm:grid-cols-4">
                @foreach ([['A', '85+', 'Excellent history', 'text-verified'], ['B', '70–84', 'Strong history', 'text-documented'], ['C', '50–69', 'Fair history', 'text-warn'], ['D', '0–49', 'Thin history', 'text-danger']] as [$g, $range, $label, $color])
                    <div class="rounded-xl bg-paper p-4"><p class="display text-3xl {{ $color }}">{{ $g }}</p><p class="num text-xs text-muted">{{ $range }}</p><p class="mt-1 font-medium">{{ $label }}</p></div>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-line bg-paper-deep/60 p-6 text-sm text-ink-soft">
            <p class="font-semibold text-ink">What a passport is not</p>
            <p class="mt-2">It isn't an inspection and it doesn't replace one. It also doesn't include accident or title-brand data from insurers and auctions — pair it with a history report and an independent pre-purchase inspection.</p>
        </section>
    </div>
</x-layouts.site>
