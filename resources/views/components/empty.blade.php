@props(['icon' => 'heroicon-o-document-text', 'title', 'text' => null])

<div {{ $attributes->class('flex flex-col items-center rounded-2xl border border-dashed border-line-strong bg-surface/50 px-6 py-12 text-center') }}>
    <div class="grid size-12 place-items-center rounded-2xl bg-paper-deep text-ink-soft">
        <x-dynamic-component :component="$icon" class="size-6" />
    </div>
    <p class="mt-4 font-display text-lg font-semibold">{{ $title }}</p>
    @if ($text)
        <p class="mt-1 max-w-sm text-sm text-muted">{{ $text }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
