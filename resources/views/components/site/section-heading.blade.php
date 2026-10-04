@props(['eyebrow' => null, 'title', 'align' => 'left'])

<div {{ $attributes->class(['mb-10', 'text-center' => $align === 'center']) }}>
    @if ($eyebrow)
        <p class="eyebrow mb-3">{{ $eyebrow }}</p>
    @endif
    <h2 class="heading-display text-4xl sm:text-5xl">{{ $title }}</h2>
    @if ($slot->isNotEmpty())
        <p @class(['mt-4 max-w-2xl text-mist', 'mx-auto' => $align === 'center'])>{{ $slot }}</p>
    @endif
</div>
