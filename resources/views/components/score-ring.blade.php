@props(['score', 'size' => 'md', 'label' => true])

@php
    $total = is_array($score) ? $score['total'] : (int) $score;
    [$grade] = \App\Services\PassportScore::grade($total);
    $dims = ['sm' => [44, 4, 'text-sm'], 'md' => [72, 6, 'text-xl'], 'lg' => [120, 9, 'text-4xl']][$size];
    [$px, $stroke, $text] = $dims;
    $r = ($px - $stroke) / 2;
    $c = 2 * M_PI * $r;
    $color = match ($grade) { 'A' => '#0d7a4b', 'B' => '#1f5cbd', 'C' => '#a85a06', default => '#be2a2f' };
@endphp

<div {{ $attributes->class('relative inline-grid shrink-0 place-items-center') }} style="width: {{ $px }}px; height: {{ $px }}px" title="Passport Score {{ $total }}/100">
    <svg width="{{ $px }}" height="{{ $px }}" class="-rotate-90" aria-hidden="true">
        <circle cx="{{ $px / 2 }}" cy="{{ $px / 2 }}" r="{{ $r }}" fill="none" stroke="#e3dfd5" stroke-width="{{ $stroke }}" />
        <circle cx="{{ $px / 2 }}" cy="{{ $px / 2 }}" r="{{ $r }}" fill="none" stroke="{{ $color }}" stroke-width="{{ $stroke }}" stroke-linecap="round"
            stroke-dasharray="{{ $c }}" stroke-dashoffset="{{ $c * (1 - $total / 100) }}" />
    </svg>
    <span class="absolute inset-0 grid place-items-center">
        <span class="text-center leading-none">
            <span class="num {{ $text }} block font-semibold text-ink">{{ $total }}</span>
            @if ($label && $size !== 'sm')
                <span class="mt-1 block font-mono text-[9px] tracking-widest text-muted uppercase">Grade {{ $grade }}</span>
            @endif
        </span>
    </span>
</div>
