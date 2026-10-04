@props(['vehicle', 'url' => null])

@php($src = $url ?? $vehicle->coverPhotoUrl())

{{-- Placeholder sits underneath; the photo covers it, and removes itself if it fails to load. --}}
<div {{ $attributes->class('relative overflow-hidden bg-paper-deep') }}>
    <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-line-strong" aria-hidden="true">
        <svg viewBox="0 0 120 48" class="w-2/5 max-w-40" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 34h4m22 0h50m22 0h8V26c0-3-2-5-5-5.6L94 18 80 8.5C77.5 7 75 6 72 6H46c-4 0-7 1.4-9.8 4L27 18l-14 2.6C9 21.4 6 24 6 28z" />
            <circle cx="21" cy="35" r="7" /><circle cx="93" cy="35" r="7" />
            <path d="M40 18h52M58 7v11" />
        </svg>
        <span class="font-mono text-[10px] tracking-[0.2em] text-muted/70 uppercase">{{ $vehicle->make }}</span>
    </div>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $vehicle->title() }}" loading="lazy" class="absolute inset-0 size-full object-cover" onerror="this.remove()">
    @endif
</div>
