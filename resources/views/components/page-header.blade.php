@props(['title', 'eyebrow' => null, 'text' => null])

<div {{ $attributes->class('flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between') }}>
    <div>
        @if ($eyebrow)
            <p class="eyebrow mb-2">{{ $eyebrow }}</p>
        @endif
        <h1 class="display text-3xl sm:text-4xl">{{ $title }}</h1>
        @if ($text)
            <p class="mt-2 max-w-2xl text-ink-soft">{{ $text }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>
