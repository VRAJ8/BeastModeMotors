{{-- Monthly spend. $bars: list of ['label' => 'Jan', 'value' => cents]. --}}
@props(['bars', 'height' => 160])

@php
    $max = max(1, collect($bars)->max('value'));
    $count = max(1, count($bars));
@endphp

<div {{ $attributes->class('flex items-end gap-1.5') }} style="height: {{ $height }}px" role="img" aria-label="Spending per month">
    @foreach ($bars as $bar)
        <div class="group flex h-full flex-1 flex-col justify-end gap-1.5">
            <div class="relative w-full rounded-t-md bg-ink transition group-hover:bg-accent" style="height: {{ max($bar['value'] > 0 ? 3 : 0, $bar['value'] / $max * 100) }}%">
                <span class="pointer-events-none absolute -top-6 left-1/2 hidden -translate-x-1/2 rounded bg-ink px-1.5 py-0.5 font-mono text-[10px] whitespace-nowrap text-white group-hover:block">{{ money($bar['value']) }}</span>
            </div>
            <span class="text-center font-mono text-[10px] text-muted">{{ $bar['label'] }}</span>
        </div>
    @endforeach
</div>
