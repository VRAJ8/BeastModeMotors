<x-layouts.site title="Add a car" robots="noindex">
    <div class="container-x py-10">
        <a href="{{ route('garage') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-heroicon-m-arrow-left class="size-4" /> Garage</a>
        <x-page-header class="mx-auto mt-4 mb-8 max-w-2xl" eyebrow="New passport" title="Add a car" text="Two minutes now, and every service, receipt and reading has somewhere to live." />
        <livewire:add-vehicle />
    </div>
</x-layouts.site>
