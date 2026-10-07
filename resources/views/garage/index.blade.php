<x-layouts.site title="Garage" robots="noindex">
    <div class="container-x py-10">
        <x-page-header eyebrow="{{ now()->format('l, F j') }}" title="Hi {{ str(auth()->user()->name)->before(' ') }}">
            <x-slot:actions>
                <a href="{{ route('vehicles.create') }}" class="btn-primary"><x-heroicon-m-plus class="size-4" /> Add a car</a>
            </x-slot:actions>
        </x-page-header>

        @if ($vehicles->isEmpty())
            <div class="card mt-8 grid overflow-hidden lg:grid-cols-2">
                <div class="p-8 sm:p-12">
                    <p class="eyebrow">Start here</p>
                    <h2 class="display mt-2 text-3xl">Give your car a passport</h2>
                    <p class="mt-3 text-ink-soft">Enter the VIN and we'll decode the car, check for open safety recalls and set up a maintenance plan. Then log services as they happen — and ask the shop to confirm them.</p>
                    <a href="{{ route('vehicles.create') }}" class="btn-accent mt-6">Add your first car</a>
                </div>
                <div class="grain hidden items-center justify-center border-l border-line bg-paper p-10 lg:flex">
                    <ol class="space-y-4 text-sm">
                        @foreach (['Decode the VIN', 'Log your last service with the receipt', 'Ask the shop to verify it', 'Share the passport or sell with it'] as $i => $step)
                            <li class="flex items-center gap-3"><span class="num grid size-7 place-items-center rounded-full bg-ink text-xs text-white">{{ $i + 1 }}</span>{{ $step }}</li>
                        @endforeach
                    </ol>
                </div>
            </div>
        @else
            <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_340px]">
                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach ($vehicles as $vehicle)
                        @php($score = $scores[$vehicle->id])
                        <a href="{{ route('vehicles.show', $vehicle) }}" class="card group overflow-hidden transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-ink/5">
                            <x-car-photo :vehicle="$vehicle" class="aspect-[16/9]" />
                            <div class="flex items-start gap-4 p-5">
                                <div class="min-w-0 flex-1">
                                    <h2 class="font-display text-xl font-semibold tracking-tight">{{ $vehicle->displayName() }}</h2>
                                    <p class="truncate text-sm text-muted">{{ $vehicle->nickname ? $vehicle->fullTitle() : ($vehicle->trim ?: $vehicle->make) }}</p>
                                    <p class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-xs text-ink-soft">
                                        <span class="num">{{ miles($vehicle->current_mileage) }}</span>
                                        <span>{{ $vehicle->records->count() }} records</span>
                                        @if ($vehicle->openListing?->isPublic())<span class="font-semibold text-verified">For sale</span>@endif
                                    </p>
                                </div>
                                <x-score-ring :score="$score" size="md" />
                            </div>
                        </a>
                    @endforeach
                    <a href="{{ route('vehicles.create') }}" class="flex min-h-48 flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-line-strong text-sm font-semibold text-muted transition hover:border-ink hover:text-ink">
                        <x-heroicon-o-plus-circle class="size-8" /> Add another car
                    </a>
                </div>

                <aside class="space-y-6">
                    <section class="card card-pad">
                        <p class="eyebrow">Needs attention</p>
                        @forelse ($alerts->take(6) as $alert)
                            <a href="{{ $alert['url'] }}" class="mt-3 flex gap-3 rounded-xl p-2 -mx-2 hover:bg-paper">
                                <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-danger' => $alert['tone'] === 'danger', 'bg-warn' => $alert['tone'] === 'warning'])></span>
                                <span class="text-sm"><span class="font-medium">{{ $alert['title'] }}</span><span class="block text-xs text-muted">{{ $alert['meta'] }}</span></span>
                            </a>
                        @empty
                            <p class="mt-3 flex items-center gap-2 text-sm text-verified"><x-heroicon-s-check-circle class="size-5" /> All clear. Nothing due.</p>
                        @endforelse
                        @if ($alerts->count() > 6)
                            <p class="mt-3 text-xs text-muted">+ {{ $alerts->count() - 6 }} more</p>
                        @endif
                        @if ($pendingVerifications)
                            <p class="mt-4 rounded-xl bg-documented-soft p-3 text-xs text-documented">{{ $pendingVerifications }} {{ str('record')->plural($pendingVerifications) }} waiting for a shop to confirm.</p>
                        @endif
                    </section>

                    <section class="card card-pad">
                        <div class="flex items-center justify-between">
                            <p class="eyebrow">Active deals</p>
                            <a href="{{ route('deals.index') }}" class="text-xs font-semibold text-muted hover:text-ink">All</a>
                        </div>
                        @forelse ($deals as $deal)
                            <a href="{{ route('deals.show', $deal) }}" class="mt-3 -mx-2 flex items-center gap-3 rounded-xl p-2 hover:bg-paper">
                                <x-car-photo :vehicle="$deal->vehicle" class="h-10 w-14 shrink-0 rounded-lg" />
                                <span class="min-w-0 text-sm"><span class="block truncate font-medium">{{ $deal->vehicle->title() }}</span>
                                    <span class="text-xs text-muted">{{ $deal->buyer_id === auth()->id() ? 'Buying from '.$deal->seller->publicName() : 'Selling to '.$deal->buyer->publicName() }} · {{ $deal->status->getLabel() }}</span></span>
                            </a>
                        @empty
                            <p class="mt-3 text-sm text-muted">No deals in progress. <a href="{{ route('marketplace') }}" class="link">Browse cars</a></p>
                        @endforelse
                    </section>
                </aside>
            </div>
        @endif
    </div>
</x-layouts.site>
