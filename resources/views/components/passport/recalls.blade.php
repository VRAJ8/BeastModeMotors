@props(['vehicle'])

@php
    $open = $vehicle->recalls->filter->isOpen();
    $resolved = $vehicle->recalls->reject->isOpen();
@endphp

<section {{ $attributes->class('card card-pad') }}>
    <p class="eyebrow">Safety recalls</p>
    @if ($vehicle->recalls->isEmpty())
        <p class="mt-3 flex items-center gap-2 text-sm"><x-heroicon-s-shield-check class="size-5 text-verified" /> No recalls on file{{ $vehicle->recalls_checked_at ? ' (checked '.$vehicle->recalls_checked_at->diffForHumans().')' : '' }}</p>
    @else
        <ul class="mt-3 space-y-2 text-sm">
            @foreach ($open as $recall)
                <li class="flex gap-2"><x-heroicon-s-exclamation-circle class="mt-0.5 size-4 shrink-0 text-danger" /> <span><span class="font-medium">Open:</span> {{ $recall->component }}</span></li>
            @endforeach
            @foreach ($resolved as $recall)
                <li class="flex gap-2 text-ink-soft"><x-heroicon-s-check-circle class="mt-0.5 size-4 shrink-0 text-verified" /> <span>Fixed: {{ $recall->component }}</span></li>
            @endforeach
        </ul>
    @endif
</section>
