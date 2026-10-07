@props(['vehicle', 'names' => false])

<section {{ $attributes->class('card card-pad') }}>
    <p class="eyebrow">Ownership</p>
    <ol class="mt-4 space-y-4">
        @foreach ($vehicle->ownerships as $ownership)
            <li class="flex gap-3">
                <span @class(['grid size-8 shrink-0 place-items-center rounded-full font-mono text-xs font-bold', 'bg-ink text-white' => $ownership->isCurrent(), 'bg-paper-deep text-ink-soft' => ! $ownership->isCurrent()])>{{ $ownership->owner_number }}</span>
                <div class="text-sm">
                    <p class="font-medium">
                        {{ $names && $ownership->user ? $ownership->user->name : $ownership->label() }}
                        @if ($ownership->isCurrent())
                            <span class="badge-orange ml-1">Current</span>
                        @endif
                    </p>
                    <p class="text-xs text-muted">{{ $ownership->period() }} · {{ $ownership->acquired_via->getLabel() }}</p>
                    <p class="num text-xs text-muted">{{ miles($ownership->start_mileage) }} → {{ $ownership->end_mileage !== null ? miles($ownership->end_mileage) : miles($vehicle->current_mileage) }}</p>
                </div>
            </li>
        @endforeach
    </ol>
</section>
