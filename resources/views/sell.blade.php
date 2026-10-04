<x-layouts.storefront title="Sell or trade in" description="Get an instant indicative valuation for your performance or luxury car, then a firm offer after a 20-minute appraisal.">
    <x-site.page-header eyebrow="Sell / Trade-in" title="What's your car worth?">
        An indicative valuation in seconds. A firm, no-obligation offer after a 20-minute appraisal — at home or at our showroom.
    </x-site.page-header>

    <div class="container-x grid gap-12 py-12 lg:grid-cols-[1fr_22rem]">
        <livewire:trade-in-form />

        <aside class="space-y-6">
            @foreach ([
                ['n' => '01', 't' => 'Instant estimate', 'b' => 'Our model weighs age, mileage and condition against the car\'s original price.'],
                ['n' => '02', 't' => 'Quick appraisal', 'b' => 'A specialist inspects the car and checks its history — usually in 20 minutes.'],
                ['n' => '03', 't' => 'Paid same day', 'b' => 'Accept the offer and we settle any finance and pay you by wire, same day.'],
            ] as $step)
                <div class="flex gap-4">
                    <span class="font-display text-4xl text-gold">{{ $step['n'] }}</span>
                    <div>
                        <h3 class="font-display text-2xl tracking-wide text-white">{{ $step['t'] }}</h3>
                        <p class="text-sm text-mist">{{ $step['b'] }}</p>
                    </div>
                </div>
            @endforeach
            <p class="rounded-lg border border-white/5 p-4 text-xs text-mist">Online estimates are indicative only and are not an offer to purchase. Final value depends on inspection, history and market demand.</p>
        </aside>
    </div>
</x-layouts.storefront>
