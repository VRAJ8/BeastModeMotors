<footer class="mt-24 border-t border-white/5 bg-carbon">
    <div class="container-x grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-4">
        <div class="space-y-4">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/bmm-logo.png') }}" alt="" class="h-14 w-14 object-contain">
                <span class="font-display text-2xl tracking-wider text-white">Beast Mode <span class="text-gold">Motors</span></span>
            </a>
            <p class="text-sm leading-relaxed text-mist">Curated exotic, luxury and performance cars. Every vehicle inspected, every price transparent.</p>
        </div>

        <div>
            <h3 class="eyebrow mb-4">Showroom</h3>
            <ul class="space-y-2 text-sm">
                <li><a class="hover:text-gold" href="{{ route('vehicles.index') }}">All inventory</a></li>
                <li><a class="hover:text-gold" href="{{ route('vehicles.index', ['condition' => 'new']) }}">New arrivals</a></li>
                <li><a class="hover:text-gold" href="{{ route('vehicles.index', ['body' => 'suv']) }}">Super SUVs</a></li>
                <li><a class="hover:text-gold" href="{{ route('vehicles.index', ['body' => 'hypercar']) }}">Hypercars</a></li>
                <li><a class="hover:text-gold" href="{{ route('compare') }}">Compare cars</a></li>
            </ul>
        </div>

        <div>
            <h3 class="eyebrow mb-4">Services</h3>
            <ul class="space-y-2 text-sm">
                <li><a class="hover:text-gold" href="{{ route('sell') }}">Sell or trade in</a></li>
                <li><a class="hover:text-gold" href="{{ route('contact', ['topic' => 'finance']) }}">Financing</a></li>
                <li><a class="hover:text-gold" href="{{ route('brands.index') }}">Brands</a></li>
                <li><a class="hover:text-gold" href="{{ route('about') }}">About us</a></li>
            </ul>
        </div>

        <div class="space-y-3 text-sm">
            <h3 class="eyebrow mb-4">Visit</h3>
            <p>{{ config('dealership.address') }}</p>
            <p><a class="hover:text-gold" href="tel:{{ preg_replace('/[^+\d]/', '', config('dealership.phone')) }}">{{ config('dealership.phone') }}</a></p>
            <p><a class="hover:text-gold" href="mailto:{{ config('dealership.email') }}">{{ config('dealership.email') }}</a></p>
            <p class="text-mist">Mon–Fri 10–7 · Sat 10–5 · Sun closed</p>
        </div>
    </div>
    <div class="border-t border-white/5">
        <div class="container-x flex flex-col items-center justify-between gap-2 py-6 text-xs text-mist sm:flex-row">
            <p>&copy; {{ date('Y') }} {{ config('dealership.name') }}. A portfolio project built with Laravel.</p>
            <p>Prices exclude taxes, title and registration. Vehicle photos are illustrative.</p>
        </div>
    </div>
</footer>
