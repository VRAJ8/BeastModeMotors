@props(['vehicle', 'active', 'title' => null])

@php
    $score = app(\App\Services\PassportScore::class)->for($vehicle);
    $openRecalls = $vehicle->recalls->filter->isOpen()->count();
    $overdue = $vehicle->reminders->filter(fn ($r) => $r->status($vehicle->current_mileage) === \App\Models\Reminder::OVERDUE)->count();
    $tabs = [
        'overview' => ['Overview', route('vehicles.show', $vehicle), null],
        'history' => ['History', route('vehicles.history', $vehicle), $vehicle->records->count() ?: null],
        'maintenance' => ['Maintenance', route('vehicles.maintenance', $vehicle), $overdue ?: null],
        'documents' => ['Documents', route('vehicles.documents', $vehicle), null],
        'costs' => ['Costs', route('vehicles.costs', $vehicle), null],
        'recalls' => ['Recalls', route('vehicles.recalls', $vehicle), $openRecalls ?: null],
        'share' => ['Share', route('vehicles.share', $vehicle), null],
        'sell' => ['Sell', route('vehicles.sell', $vehicle), null],
    ];
    $alertTabs = ['maintenance' => $overdue, 'recalls' => $openRecalls];
@endphp

<x-layouts.site :title="($title ? $title.' · ' : '').$vehicle->displayName()" robots="noindex">
    <section class="border-b border-line bg-surface/60">
        <div class="container-x pt-6">
            <a href="{{ route('garage') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-heroicon-m-arrow-left class="size-4" /> Garage</a>

            <div class="mt-4 flex flex-wrap items-center gap-5">
                <x-car-photo :vehicle="$vehicle" class="hidden h-20 w-32 shrink-0 rounded-xl sm:block" />
                <div class="min-w-0 flex-1">
                    <h1 class="display truncate text-2xl sm:text-3xl">{{ $vehicle->displayName() }}</h1>
                    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted">
                        @if ($vehicle->nickname)<span>{{ $vehicle->fullTitle() }}</span>@elseif ($vehicle->trim)<span>{{ $vehicle->trim }}</span>@endif
                        <span class="vin text-xs">{{ $vehicle->vin }}</span>
                        <span class="num">{{ miles($vehicle->current_mileage) }}</span>
                        @if ($vehicle->openListing?->isPublic())
                            <a href="{{ route('listings.show', $vehicle->openListing) }}" class="badge-green">For sale · {{ money($vehicle->openListing->price_cents) }}</a>
                        @endif
                    </p>
                </div>
                <a href="{{ route('vehicles.show', $vehicle) }}" class="flex items-center gap-3 rounded-2xl border border-line bg-surface py-2 pr-4 pl-2">
                    <x-score-ring :score="$score" size="sm" />
                    <span class="text-xs leading-tight"><span class="block font-semibold">Passport Score</span><span class="text-muted">{{ $score['label'] }}</span></span>
                </a>
            </div>

            <nav class="-mb-px mt-6 flex gap-6 overflow-x-auto" aria-label="Car sections">
                @foreach ($tabs as $key => [$label, $url, $count])
                    <a href="{{ $url }}" @class(['tab', 'tab-active' => $active === $key]) @if ($active === $key) aria-current="page" @endif>
                        {{ $label }}
                        @if ($count)
                            <span @class(['num rounded-full px-1.5 text-[10px] font-semibold', 'bg-danger text-white' => isset($alertTabs[$key]), 'bg-paper-deep text-ink-soft' => ! isset($alertTabs[$key])])>{{ $count }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>
    </section>

    <div class="container-x py-8">
        {{ $slot }}
    </div>
</x-layouts.site>
