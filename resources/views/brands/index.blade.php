<x-layouts.storefront title="Brands" description="The marques we specialise in — Lamborghini, Ferrari, McLaren, Porsche, Rolls-Royce and more.">
    <x-site.page-header eyebrow="The marques" title="Our brands">
        We specialise in the world's most storied performance and luxury manufacturers.
    </x-site.page-header>

    <div class="container-x grid gap-6 py-12 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($brands as $brand)
            <a href="{{ route('brands.show', $brand) }}" class="card group relative flex min-h-64 flex-col justify-end overflow-hidden p-6">
                @if ($cover = $brand->vehicles->first()?->cover_image)
                    <img src="{{ $cover }}" data-fallback="{{ asset(\App\Models\Vehicle::PLACEHOLDER_IMAGE) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover opacity-40 transition duration-700 group-hover:scale-105 group-hover:opacity-55">
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/60 to-transparent"></div>
                <div class="relative">
                    <p class="text-xs tracking-widest text-mist uppercase">{{ $brand->country }} · est. {{ $brand->founded_year }}</p>
                    <h2 class="font-display text-4xl tracking-wide text-white group-hover:text-gold">{{ $brand->name }}</h2>
                    <p class="mt-1 text-sm text-gold">{{ $brand->vehicles_count }} {{ str('car')->plural($brand->vehicles_count) }} available</p>
                </div>
            </a>
        @endforeach
    </div>
</x-layouts.storefront>
