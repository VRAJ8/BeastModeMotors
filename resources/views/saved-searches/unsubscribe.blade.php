<x-layouts.site title="Saved-search emails" robots="noindex">
    <div class="container-x max-w-lg py-16 text-center">
        <x-heroicon-o-bell-slash class="mx-auto size-10 text-muted" />
        @if ($done)
            <h1 class="display mt-4 text-3xl">You won't get these emails any more</h1>
            <p class="mt-2 text-sm text-muted">Your saved searches are still there. You can turn emails back on for any of them from your Saved page.</p>
            <a href="{{ route('saved') }}" class="btn-secondary mt-6">Saved searches</a>
        @else
            <h1 class="display mt-4 text-3xl">Stop saved-search emails?</h1>
            <p class="mt-2 text-sm text-muted">You'll stop getting emails about new cars that match your saved searches. The searches themselves stay saved.</p>
            <form method="POST" class="mt-6">
                @csrf
                <button class="btn-primary">Stop the emails</button>
            </form>
        @endif
    </div>
</x-layouts.site>
