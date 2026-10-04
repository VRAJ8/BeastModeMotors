@props(['vehicle', 'anomalies' => [], 'milesPerYear' => null])

@php
    $flaggedIds = collect($anomalies)->pluck('id')->all();
    $points = $vehicle->readings->map(fn ($r) => [$r->recorded_on->timestamp, $r->reading])->all();
    $flagged = $vehicle->readings->whereIn('id', $flaggedIds)->map(fn ($r) => $r->recorded_on->timestamp)->values()->all();
@endphp

<section {{ $attributes->class('card card-pad') }}>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="eyebrow">Odometer</p>
            <p class="num mt-1 text-2xl font-semibold">{{ miles($vehicle->current_mileage) }}</p>
        </div>
        <div class="flex gap-6 text-right">
            <div>
                <p class="eyebrow">Readings</p>
                <p class="num mt-1 font-semibold">{{ $vehicle->readings->count() }}</p>
            </div>
            <div>
                <p class="eyebrow">Avg / year</p>
                <p class="num mt-1 font-semibold">{{ $milesPerYear ? number_format($milesPerYear) : '—' }}</p>
            </div>
        </div>
    </div>

    @if ($vehicle->readings->count() > 1)
        <x-chart.line class="mt-4" :points="$points" :flagged="$flagged" />
    @else
        <p class="mt-4 text-sm text-muted">More readings will draw the mileage history here.</p>
    @endif

    @if ($anomalies)
        <div class="mt-4 rounded-xl bg-danger-soft p-3 text-sm text-danger">
            <p class="flex items-center gap-1.5 font-semibold"><x-heroicon-s-exclamation-triangle class="size-4" /> Odometer went backwards</p>
            <ul class="mt-1 space-y-0.5 text-xs">
                @foreach ($anomalies as $a)
                    <li><span class="num">{{ \Illuminate\Support\Carbon::parse($a['date'])->format('M j, Y') }}</span>: {{ number_format($a['reading']) }} mi after an earlier {{ number_format($a['previous_max']) }} mi</li>
                @endforeach
            </ul>
        </div>
    @else
        <p class="mt-3 flex items-center gap-1.5 text-xs font-medium text-verified"><x-heroicon-s-check-circle class="size-4" /> Readings only ever go up</p>
    @endif
</section>
