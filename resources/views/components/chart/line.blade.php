{{-- Mileage over time. $points: list of [timestamp, value]; $flagged: timestamps to mark in red. --}}
@props(['points', 'flagged' => [], 'height' => 180])

@php
    $w = 640; $h = $height; $padL = 52; $padR = 12; $padT = 14; $padB = 26;
    $points = collect($points)->sortBy(0)->values();
    $minX = $points->min(0); $maxX = $points->max(0);
    $maxY = max(1, (int) ceil($points->max(1) * 1.08 / 1000) * 1000);
    $sx = fn ($x) => $padL + ($maxX == $minX ? ($w - $padL - $padR) / 2 : ($x - $minX) / ($maxX - $minX) * ($w - $padL - $padR));
    $sy = fn ($y) => $padT + (1 - $y / $maxY) * ($h - $padT - $padB);
    $path = $points->map(fn ($p, $i) => ($i ? 'L' : 'M').round($sx($p[0]), 1).' '.round($sy($p[1]), 1))->implode(' ');
    $area = $points->isEmpty() ? '' : $path.' L'.round($sx($points->last()[0]), 1).' '.($h - $padB).' L'.round($sx($points->first()[0]), 1).' '.($h - $padB).' Z';
    $ticks = collect(range(0, 3))->map(fn ($i) => (int) round($maxY / 3 * $i));
    $years = $points->isEmpty() ? collect() : collect(range((int) date('Y', $minX), (int) date('Y', $maxX)))->filter(fn ($y) => mktime(0, 0, 0, 1, 1, $y) >= $minX);
@endphp

<svg viewBox="0 0 {{ $w }} {{ $h }}" {{ $attributes->class('w-full') }} role="img" aria-label="Odometer readings over time">
    @foreach ($ticks as $tick)
        <line x1="{{ $padL }}" x2="{{ $w - $padR }}" y1="{{ $sy($tick) }}" y2="{{ $sy($tick) }}" stroke="#e3dfd5" stroke-dasharray="{{ $tick ? '3 4' : '' }}" />
        <text x="{{ $padL - 8 }}" y="{{ $sy($tick) + 4 }}" text-anchor="end" class="fill-muted font-mono text-[10px]">{{ $tick >= 1000 ? round($tick / 1000).'k' : $tick }}</text>
    @endforeach
    @foreach ($years as $year)
        @php($x = $sx(mktime(0, 0, 0, 1, 1, $year)))
        <text x="{{ $x }}" y="{{ $h - 8 }}" text-anchor="middle" class="fill-muted font-mono text-[10px]">{{ $year }}</text>
    @endforeach
    @if ($points->count() > 1)
        <path d="{{ $area }}" fill="#ff5b14" fill-opacity="0.07" />
        <path d="{{ $path }}" fill="none" stroke="#121417" stroke-width="2" stroke-linejoin="round" />
    @endif
    @foreach ($points as [$x, $y])
        @php($bad = in_array($x, $flagged, true))
        <circle cx="{{ $sx($x) }}" cy="{{ $sy($y) }}" r="{{ $bad ? 5 : 3 }}" fill="{{ $bad ? '#be2a2f' : '#fff' }}" stroke="{{ $bad ? '#be2a2f' : '#121417' }}" stroke-width="1.6">
            <title>{{ date('M j, Y', $x) }} · {{ number_format($y) }} mi{{ $bad ? ' — lower than an earlier reading' : '' }}</title>
        </circle>
    @endforeach
</svg>
