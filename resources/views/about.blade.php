<x-layouts.storefront title="About" description="Beast Mode Motors — a Miami showroom for exotic and performance cars, built on transparency and obsession.">
    <x-site.page-header eyebrow="Our story" title="Built by enthusiasts">
        Beast Mode Motors started with a simple idea: buying an extraordinary car should be as thrilling as driving one.
    </x-site.page-header>

    <div class="container-x grid gap-16 py-16 lg:grid-cols-2 lg:items-center">
        <div class="space-y-5 text-lg leading-relaxed text-silver">
            <p>We're a small team of car obsessives, ex-race engineers and specialists who got tired of opaque pricing, pushy sales tactics and showrooms that felt like museums.</p>
            <p>So we built the dealership we wanted to buy from: every car hand-selected and inspected, every price published, and every customer treated like a fellow enthusiast — whether you're buying your first 911 or your fifth hypercar.</p>
            <p>Our platform lets you browse, compare, finance, book a test drive or value your trade-in without a phone call. When you do want to talk, a real specialist picks up.</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
            @foreach ([['15+', 'Years in exotics'], ['1,200+', 'Cars delivered'], ['200', 'Point inspection'], ['4.9★', 'Average rating']] as [$value, $label])
                <div class="card p-8 text-center">
                    <p class="font-display text-5xl text-gold">{{ $value }}</p>
                    <p class="mt-1 text-xs tracking-widest text-mist uppercase">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <section class="container-x">
        <x-site.section-heading eyebrow="What we stand for" title="Our principles" align="center" />
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ([
                ['Curated, not crowded', 'We only list cars we would buy ourselves. If it doesn\'t pass inspection, it doesn\'t reach the floor.'],
                ['Radical transparency', 'Published prices, public price drops, full history files and honest condition reports.'],
                ['Enthusiasts first', 'Track-day advice, introductions to the best detailers, and an owners\' community that meets monthly.'],
            ] as [$title, $body])
                <div class="card p-8">
                    <div class="mb-4 h-1 w-10 rounded bg-gold"></div>
                    <h3 class="font-display text-3xl tracking-wide text-white">{{ $title }}</h3>
                    <p class="mt-2 text-mist">{{ $body }}</p>
                </div>
            @endforeach
        </div>
        <div class="mt-16 text-center">
            <a href="{{ route('vehicles.index') }}" class="btn-gold" wire:navigate>See what's on the floor</a>
        </div>
    </section>
</x-layouts.storefront>
