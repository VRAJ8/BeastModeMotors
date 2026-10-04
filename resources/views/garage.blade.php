<x-layouts.storefront title="My Garage">
    <x-site.page-header eyebrow="Welcome back, {{ str(auth()->user()->name)->before(' ') }}" title="My garage">
        Your saved cars, test drives, offers and alerts — all in one place.
    </x-site.page-header>

    <div class="container-x grid gap-10 py-12 lg:grid-cols-[1fr_22rem]">
        <div class="space-y-14">
            {{-- Saved cars --}}
            <section>
                <div class="mb-6 flex items-end justify-between">
                    <h2 class="heading-display text-3xl">Saved cars <span class="text-mist">({{ $favorites->count() }})</span></h2>
                    @if ($favorites->count() > 1)
                        <p class="text-sm text-mist">Tip: add them to compare with ⇄</p>
                    @endif
                </div>
                @if ($favorites->isEmpty())
                    <div class="card px-6 py-12 text-center">
                        <p class="text-mist">Tap the ♥ on any car to save it here. We'll email you if its price drops.</p>
                        <a href="{{ route('vehicles.index') }}" class="btn-gold mt-5" wire:navigate>Browse inventory</a>
                    </div>
                @else
                    <div class="grid gap-6 sm:grid-cols-2">
                        @foreach ($favorites as $vehicle)
                            <x-vehicle-card :vehicle="$vehicle" />
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Test drives --}}
            <section>
                <h2 class="heading-display mb-6 text-3xl">Test drives</h2>
                @if ($upcoming->isEmpty() && $past->isEmpty())
                    <div class="card px-6 py-10 text-center text-mist">No test drives yet. Pick a car and book a slot in seconds.</div>
                @endif

                <div class="space-y-3">
                    @foreach ($upcoming as $drive)
                        <div class="card flex flex-col gap-4 p-4 sm:flex-row sm:items-center">
                            <img src="{{ $drive->vehicle->cover_image }}" data-fallback="{{ asset(\App\Models\Vehicle::PLACEHOLDER_IMAGE) }}" alt="" class="h-20 w-full rounded-md object-cover sm:w-32">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="badge {{ $drive->status === \App\Enums\TestDriveStatus::Confirmed ? 'bg-sky-600' : 'bg-amber-500' }} text-white">{{ $drive->status->getLabel() }}</span>
                                    <span class="font-mono text-xs text-mist">{{ $drive->reference }}</span>
                                </div>
                                <a href="{{ route('vehicles.show', $drive->vehicle) }}" class="mt-1 block font-semibold text-white hover:text-gold">{{ $drive->vehicle->title }} {{ $drive->vehicle->trim }}</a>
                                <p class="text-sm text-gold">{{ $drive->scheduled_at->format('l, M j · g:i A') }}</p>
                            </div>
                            @if ($drive->isCancellable())
                                <form method="POST" action="{{ route('garage.test-drives.cancel', $drive) }}" onsubmit="return confirm('Cancel this test drive?')">
                                    @csrf @method('PATCH')
                                    <button class="btn-outline px-4 py-2 text-xs hover:border-ember hover:text-ember">Cancel</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($past->isNotEmpty())
                    <h3 class="eyebrow mt-8 mb-3">History</h3>
                    <ul class="divide-y divide-white/5 rounded-xl border border-white/5">
                        @foreach ($past as $drive)
                            <li class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                                <span class="text-silver">{{ $drive->vehicle->title }} {{ $drive->vehicle->trim }}</span>
                                <span class="text-mist">{{ $drive->scheduled_at->format('M j, Y') }} · {{ $drive->status->getLabel() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Enquiries --}}
            <section>
                <h2 class="heading-display mb-6 text-3xl">Offers &amp; enquiries</h2>
                @if ($leads->isEmpty())
                    <div class="card px-6 py-10 text-center text-mist">Offers, trade-in requests and messages you send will appear here.</div>
                @else
                    <ul class="divide-y divide-white/5 rounded-xl border border-white/5">
                        @foreach ($leads as $lead)
                            <li class="flex flex-col gap-1 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-white">
                                        {{ $lead->type->getLabel() }}
                                        @if ($lead->vehicle) · <a href="{{ route('vehicles.show', $lead->vehicle) }}" class="hover:text-gold">{{ $lead->vehicle->title }}</a>@endif
                                        @if ($lead->offer_amount) · <span class="text-gold">{{ money($lead->offer_amount) }}</span>@endif
                                    </p>
                                    <p class="text-xs text-mist">{{ $lead->created_at->format('M j, Y') }}</p>
                                </div>
                                <span class="badge self-start border border-steel text-silver sm:self-auto">{{ $lead->status->getLabel() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        {{-- Notifications --}}
        <aside class="space-y-6">
            <div class="card p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="eyebrow">Alerts</h2>
                    @if (auth()->user()->unreadNotifications->isNotEmpty())
                        <form method="POST" action="{{ route('garage.notifications.read') }}">
                            @csrf
                            <button class="text-xs text-mist hover:text-gold">Mark all read</button>
                        </form>
                    @endif
                </div>
                @forelse ($notifications as $notification)
                    <a href="{{ $notification->data['url'] ?? '#' }}" class="-mx-2 flex gap-3 rounded-md px-2 py-3 hover:bg-white/5">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-steel' : 'bg-gold' }}"></span>
                        <span>
                            <span class="block text-sm text-white">{{ $notification->data['message'] ?? 'Update' }}</span>
                            <span class="text-xs text-mist">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                    </a>
                @empty
                    <p class="text-sm text-mist">No alerts yet. Save a car and we'll let you know when its price drops.</p>
                @endforelse
            </div>

            <div class="card p-6">
                <h2 class="eyebrow mb-3">Account</h2>
                <p class="text-white">{{ auth()->user()->name }}</p>
                <p class="text-sm text-mist">{{ auth()->user()->email }}</p>
                <a href="{{ route('profile.edit') }}" class="link-gold mt-3 inline-block text-sm">Edit profile →</a>
            </div>

            <a href="{{ route('sell') }}" class="card block p-6 transition hover:border-gold/30">
                <p class="eyebrow mb-2">Trading up?</p>
                <p class="font-display text-2xl tracking-wide text-white">Value your current car in 30 seconds →</p>
            </a>
        </aside>
    </div>
</x-layouts.storefront>
