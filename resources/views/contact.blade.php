<x-layouts.storefront title="Contact" description="Visit our Miami showroom or send us a message — financing, sourcing, service and general questions.">
    <x-site.page-header eyebrow="Get in touch" title="Contact us">
        Questions about a car, financing, or sourcing something special? Our specialists reply within a few hours.
    </x-site.page-header>

    <div class="container-x grid gap-12 py-12 lg:grid-cols-[1fr_22rem]">
        <livewire:contact-form :topic="(string) request('topic', 'general')" />

        <aside class="space-y-4">
            <div class="card p-6">
                <h2 class="eyebrow mb-3">Showroom</h2>
                <p class="text-white">{{ config('dealership.address') }}</p>
                <a href="https://maps.google.com/?q={{ urlencode(config('dealership.address')) }}" target="_blank" rel="noopener" class="link-gold mt-2 inline-block text-sm">Get directions →</a>
            </div>
            <div class="card p-6">
                <h2 class="eyebrow mb-3">Talk to us</h2>
                <p><a class="text-white hover:text-gold" href="tel:{{ preg_replace('/[^+\d]/', '', config('dealership.phone')) }}">{{ config('dealership.phone') }}</a></p>
                <p><a class="text-white hover:text-gold" href="mailto:{{ config('dealership.email') }}">{{ config('dealership.email') }}</a></p>
            </div>
            <div class="card p-6">
                <h2 class="eyebrow mb-3">Opening hours</h2>
                <dl class="space-y-1 text-sm">
                    @foreach (config('dealership.test_drives.hours') as $day => $hours)
                        <div class="flex justify-between">
                            <dt class="text-mist">{{ \Carbon\Carbon::create()->startOfWeek()->addDays($day - 1)->format('l') }}</dt>
                            <dd class="text-white">{{ $hours ? \Carbon\Carbon::parse($hours[0])->format('g A').' – '.\Carbon\Carbon::parse($hours[1])->format('g A') : 'Closed' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </aside>
    </div>
</x-layouts.storefront>
