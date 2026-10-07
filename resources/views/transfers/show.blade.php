<x-layouts.site title="Passport transfer · {{ $transfer->vehicle->title() }}" robots="noindex">
    <div class="container-x max-w-2xl py-10">
        @auth
            <livewire:accept-transfer :transfer="$transfer" />
        @else
            @include('transfers.partials.car', ['transfer' => $transfer])
            <section class="card card-pad mt-6">
                @if ($transfer->isPending())
                    <h2 class="panel-title">Accept this passport</h2>
                    <p class="mt-1 text-sm text-muted">Sign in or create a free account, and you'll come straight back here to accept it. You'll need the car in front of you to check its VIN.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('register') }}" class="btn-primary">Create an account</a>
                        <a href="{{ route('login') }}" class="btn-secondary">Sign in</a>
                    </div>
                @else
                    <p class="text-sm text-ink-soft">This link no longer works. Ask the owner for a fresh one.</p>
                @endif
            </section>
        @endauth
    </div>
</x-layouts.site>
