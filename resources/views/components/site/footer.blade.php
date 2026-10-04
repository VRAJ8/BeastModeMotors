<footer class="no-print mt-24 border-t border-line bg-paper-deep/60">
    <div class="container-x grid gap-10 py-14 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div class="max-w-sm">
            <x-logo />
            <p class="mt-4 text-sm leading-relaxed text-ink-soft">The history of a car should belong to the car. Log it as you go, have shops confirm it, and hand it to the next owner when you sell.</p>
        </div>
        <div>
            <p class="eyebrow mb-3">Owners</p>
            <ul class="space-y-2 text-sm">
                <li><a class="text-ink-soft hover:text-ink" href="{{ route('register') }}">Start a passport</a></li>
                <li><a class="text-ink-soft hover:text-ink" href="{{ route('how-it-works') }}">How it works</a></li>
                <li><a class="text-ink-soft hover:text-ink" href="{{ route('how-it-works') }}#score">The Passport Score</a></li>
            </ul>
        </div>
        <div>
            <p class="eyebrow mb-3">Buyers</p>
            <ul class="space-y-2 text-sm">
                <li><a class="text-ink-soft hover:text-ink" href="{{ route('marketplace') }}">Cars for sale</a></li>
                <li><a class="text-ink-soft hover:text-ink" href="{{ route('vin-check') }}">Free VIN & recall check</a></li>
                <li><a class="text-ink-soft hover:text-ink" href="{{ route('safety') }}">Buying & selling safely</a></li>
            </ul>
        </div>
        <div>
            <p class="eyebrow mb-3">Data</p>
            <p class="text-sm leading-relaxed text-ink-soft">VIN decoding and recalls come from the U.S. <a class="link" href="https://www.nhtsa.gov/recalls" rel="noopener" target="_blank">NHTSA</a> open data APIs.</p>
        </div>
    </div>
    <div class="border-t border-line">
        <div class="container-x flex flex-col gap-2 py-5 text-xs text-muted sm:flex-row sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ config('passport.name') }}. We never hold buyers' or sellers' money.</p>
            <p>Made for people who look after their cars.</p>
        </div>
    </div>
</footer>
