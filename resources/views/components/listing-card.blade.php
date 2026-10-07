@props(['listing'])

@php($vehicle = $listing->vehicle)

<article {{ $attributes->class('group card relative flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-ink/5') }}>
    <x-car-photo :vehicle="$vehicle" class="aspect-[16/10]" />
    <div class="absolute top-3 left-3 flex items-center gap-2 rounded-full bg-surface/95 py-1 pr-3 pl-1 shadow-sm backdrop-blur">
        <x-score-ring :score="$listing->score" size="sm" :label="false" class="scale-[0.8]" />
        <span class="text-[11px] leading-tight font-semibold">Passport<br><span class="font-normal text-muted">{{ \App\Services\PassportScore::grade($listing->score)[1] }}</span></span>
    </div>
    @if ($listing->status === \App\Enums\ListingStatus::Pending)
        <span class="badge-amber absolute top-4 right-3">Sale agreed</span>
    @endif
    <div class="flex flex-1 flex-col p-4">
        <h3 class="font-display text-lg leading-snug font-semibold">
            <a href="{{ route('listings.show', $listing) }}" class="after:absolute after:inset-0">{{ $vehicle->title() }}</a>
        </h3>
        <p class="truncate text-sm text-muted">{{ $vehicle->trim ?: $vehicle->body }}</p>
        <div class="mt-auto flex items-end justify-between pt-4">
            <p class="num text-xl font-semibold">{{ money($listing->price_cents) }}</p>
            <p class="text-right text-xs text-muted"><span class="num">{{ miles($listing->mileage) }}</span><br>{{ $listing->location() }}</p>
        </div>
    </div>
</article>
