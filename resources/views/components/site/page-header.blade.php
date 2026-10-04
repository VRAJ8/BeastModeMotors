@props(['eyebrow' => null, 'title'])

<section class="relative overflow-hidden border-b border-white/5 bg-carbon">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(212,168,87,0.15),transparent_60%)]"></div>
    <div class="container-x relative py-14 sm:py-20">
        @if ($eyebrow)
            <p class="eyebrow mb-3">{{ $eyebrow }}</p>
        @endif
        <h1 class="heading-display text-5xl sm:text-6xl">{{ $title }}</h1>
        @if ($slot->isNotEmpty())
            <div class="mt-4 max-w-2xl text-lg text-mist">{{ $slot }}</div>
        @endif
    </div>
</section>
