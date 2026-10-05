<x-vehicle.shell :vehicle="$vehicle" active="overview">
    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <section class="grid gap-3 sm:grid-cols-4">
                @foreach ([
                    ['Log work', route('records.create', $vehicle), 'heroicon-o-wrench-screwdriver'],
                    ['Upload document', route('vehicles.documents', $vehicle), 'heroicon-o-document-arrow-up'],
                    ['Add expense', route('vehicles.costs', $vehicle), 'heroicon-o-banknotes'],
                    ['Share passport', route('vehicles.share', $vehicle), 'heroicon-o-share'],
                ] as [$label, $url, $icon])
                    <a href="{{ $url }}" class="card flex items-center gap-3 p-4 text-sm font-semibold transition hover:border-ink">
                        <span class="grid size-9 place-items-center rounded-xl bg-paper text-ink"><x-dynamic-component :component="$icon" class="size-5" /></span>{{ $label }}
                    </a>
                @endforeach
            </section>

            <x-passport.mileage :vehicle="$vehicle" :anomalies="$anomalies" :miles-per-year="$milesPerYear" />

            <section class="card card-pad">
                <livewire:vehicle.readings :vehicle="$vehicle" />
            </section>

            <section>
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="panel-title">Recent history</h2>
                    <a href="{{ route('vehicles.history', $vehicle) }}" class="link text-sm">All records</a>
                </div>
                <x-passport.timeline :records="$vehicle->records" :limit="4" :show-costs="true" :costs-for="$vehicle->currentOwnership?->id" />
            </section>
        </div>

        <aside class="space-y-6">
            <x-passport.score :score="$score" :tips="true" />

            <section class="card card-pad">
                <div class="flex items-center justify-between">
                    <p class="eyebrow">Coming up</p>
                    <a href="{{ route('vehicles.maintenance', $vehicle) }}" class="text-xs font-semibold text-muted hover:text-ink">Plan</a>
                </div>
                @forelse ($upcoming as $reminder)
                    @php($status = $reminder->status($vehicle->current_mileage))
                    <div class="mt-4">
                        <div class="flex justify-between text-sm"><span class="font-medium">{{ $reminder->task }}</span>
                            <span @class(['text-xs font-semibold', 'text-danger' => $status === 'overdue', 'text-warn' => $status === 'due_soon', 'text-muted' => $status === 'ok'])>{{ $status === 'overdue' ? 'Overdue' : $reminder->dueLabel($vehicle->current_mileage) }}</span></div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-paper-deep"><div @class(['h-full rounded-full', 'bg-danger' => $status === 'overdue', 'bg-warn' => $status === 'due_soon', 'bg-verified' => $status === 'ok']) style="width: {{ $reminder->progress($vehicle->current_mileage) }}%"></div></div>
                    </div>
                @empty
                    <p class="mt-3 text-sm text-muted">Log a service and tick what it covered to start tracking what's due.</p>
                @endforelse
            </section>

            <section class="card card-pad">
                <p class="eyebrow mb-4">Details</p>
                <x-passport.facts :vehicle="$vehicle" :full-vin="true" class="!grid-cols-2" />
                <a href="{{ route('vehicles.settings', $vehicle) }}" class="link mt-5 inline-block text-sm">Edit details</a>
            </section>

            <x-passport.owners :vehicle="$vehicle" />
            <x-passport.recalls :vehicle="$vehicle" />
        </aside>
    </div>
</x-vehicle.shell>
