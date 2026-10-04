@props(['vehicle'])

<article {{ $attributes->class('group card relative flex flex-col overflow-hidden transition duration-300 hover:-translate-y-1 hover:border-gold/30 hover:shadow-2xl hover:shadow-gold/5') }}>
    <a href="{{ route('vehicles.show', $vehicle) }}" class="relative block aspect-[16/10] overflow-hidden bg-graphite" wire:navigate>
        <img src="{{ $vehicle->cover_image }}" data-fallback="{{ asset(\App\Models\Vehicle::PLACEHOLDER_IMAGE) }}"
             alt="{{ $vehicle->title }}" loading="lazy"
             class="h-full w-full object-cover transition duration-700 group-hover:scale-105 {{ $vehicle->isAvailable() ? '' : 'grayscale' }}">
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
        <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
            @unless ($vehicle->isAvailable())
                <span class="badge {{ $vehicle->status === \App\Enums\VehicleStatus::Sold ? 'bg-ember' : 'bg-amber-500' }} text-white">{{ $vehicle->status->getLabel() }}</span>
            @endunless
            @if ($vehicle->has_price_drop && $vehicle->isAvailable())
                <span class="badge bg-emerald-600 text-white">Price drop</span>
            @endif
            @if ($vehicle->condition === \App\Enums\Condition::New)
                <span class="badge bg-white text-ink">New</span>
            @endif
        </div>
        <div class="absolute right-4 bottom-3 left-4 flex items-end justify-between">
            <span class="text-xs text-silver/90">{{ number_format($vehicle->mileage) }} mi · {{ $vehicle->drivetrain->getLabel() }}</span>
            @if ($vehicle->horsepower)
                <span class="font-display text-xl tracking-wide text-gold">{{ $vehicle->horsepower }} HP</span>
            @endif
        </div>
    </a>

    <div class="flex flex-1 flex-col gap-3 p-5">
        <div>
            <p class="text-xs font-semibold tracking-widest text-mist uppercase">{{ $vehicle->year }} · {{ $vehicle->brand->name }}</p>
            <h3 class="mt-1 font-display text-2xl leading-tight tracking-wide text-white">
                <a href="{{ route('vehicles.show', $vehicle) }}" class="hover:text-gold" wire:navigate>{{ $vehicle->model }} {{ $vehicle->trim }}</a>
            </h3>
        </div>
        <div class="mt-auto flex items-end justify-between gap-3">
            <div>
                @if ($vehicle->has_price_drop)
                    <p class="text-xs text-mist line-through">{{ money($vehicle->previous_price) }}</p>
                @endif
                <p class="text-xl font-bold text-white">{{ money($vehicle->price) }}</p>
            </div>
            <livewire:vehicle-actions :vehicle="$vehicle" compact :key="'actions-'.$vehicle->id" />
        </div>
    </div>
</article>
