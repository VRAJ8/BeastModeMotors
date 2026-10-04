<x-layouts.storefront :title="$brand->name" :description="$brand->description">
    <x-site.page-header :eyebrow="$brand->country.' · Since '.$brand->founded_year" :title="$brand->name">
        {{ $brand->description }}
    </x-site.page-header>

    <div class="container-x py-12">
        @if ($vehicles->isEmpty())
            <div class="card px-6 py-16 text-center">
                <p class="font-display text-3xl text-white">Nothing on the floor right now</p>
                <p class="mt-2 text-mist">We source {{ $brand->name }} cars to order. <a href="{{ route('contact', ['topic' => 'sourcing']) }}" class="link-gold">Tell us what you want</a>.</p>
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($vehicles as $vehicle)
                    <x-vehicle-card :vehicle="$vehicle" />
                @endforeach
            </div>
        @endif
        <p class="mt-10"><a href="{{ route('brands.index') }}" class="link-gold">← All brands</a></p>
    </div>
</x-layouts.storefront>
