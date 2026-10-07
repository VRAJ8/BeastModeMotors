<x-layouts.site title="Saved cars" robots="noindex">
    <div class="container-x py-10">
        <x-page-header eyebrow="Saved" title="Cars you're watching" />
        <div class="mt-8">
            <livewire:saved-searches />
        </div>
        <h2 class="panel-title mt-10">Saved cars</h2>
        @if ($listings->isEmpty())
            <x-empty class="mt-4" icon="heroicon-o-heart" title="Nothing saved yet" text="Tap Save on any listing to keep it here.">
                <a href="{{ route('marketplace') }}" class="btn-primary">Browse cars</a>
            </x-empty>
        @else
            <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($listings as $listing)
                    <div class="relative">
                        <x-listing-card :listing="$listing" />
                        @unless ($listing->isPublic())
                            <span class="badge-gray absolute top-4 right-3">{{ $listing->status->getLabel() }}</span>
                        @endunless
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.site>
