<x-layouts.site title="Deal room · {{ $deal->vehicle->title() }}" robots="noindex">
    <div class="container-x py-8">
        <a href="{{ route('deals.index') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-heroicon-m-arrow-left class="size-4" /> Deals</a>
        <div class="mt-4">
            <livewire:deal-room :deal="$deal" />
        </div>
    </div>
</x-layouts.site>
