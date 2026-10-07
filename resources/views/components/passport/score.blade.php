@props(['score', 'tips' => false])

<section {{ $attributes->class('card card-pad') }}>
    <div class="flex items-center gap-5">
        <x-score-ring :score="$score" size="lg" />
        <div>
            <p class="eyebrow">Passport Score</p>
            <p class="display mt-1 text-2xl">{{ $score['label'] }}</p>
            <p class="mt-1 text-sm text-muted">Rewards proof, not spending. <a href="{{ route('how-it-works') }}#score" class="link">How it's scored</a></p>
        </div>
    </div>

    <div class="mt-6 space-y-4">
        @foreach ($score['components'] as $component)
            <div>
                <div class="flex items-baseline justify-between gap-4 text-sm">
                    <span class="font-medium">{{ $component['label'] }}</span>
                    <span class="num text-xs text-muted">{{ $component['points'] }}/{{ $component['max'] }}</span>
                </div>
                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-paper-deep">
                    <div @class(['h-full rounded-full', 'bg-verified' => $component['points'] >= $component['max'] * 0.8, 'bg-warn' => $component['points'] < $component['max'] * 0.8 && $component['points'] >= $component['max'] * 0.4, 'bg-danger' => $component['points'] < $component['max'] * 0.4])
                         style="width: {{ $component['points'] / $component['max'] * 100 }}%"></div>
                </div>
                <p class="mt-1 text-xs text-muted">{{ $component['detail'] }}</p>
                @if ($tips && $component['tip'])
                    <p class="mt-1 flex gap-1.5 text-xs font-medium text-accent-dark"><x-heroicon-m-light-bulb class="size-3.5 shrink-0" /> {{ $component['tip'] }}</p>
                @endif
            </div>
        @endforeach
    </div>
</section>
