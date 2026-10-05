{{-- Read-only record timeline for passports and listings. --}}
@props(['records', 'showCosts' => false, 'documentUrl' => null, 'limit' => null])

@php
    $shown = $limit ? $records->take($limit) : $records;
    $byYear = $shown->groupBy(fn ($r) => $r->performed_on->year);
@endphp

@if ($records->isEmpty())
    <x-empty icon="heroicon-o-wrench-screwdriver" title="No records yet" text="Nothing has been logged for this car." />
@else
    <ol {{ $attributes->class('space-y-8') }}>
        @foreach ($byYear as $year => $items)
            <li>
                <p class="eyebrow mb-3">{{ $year }}</p>
                <ol class="relative space-y-3 border-l border-dashed border-line-strong pl-5">
                    @foreach ($items as $record)
                        <li class="relative">
                            <span @class(['absolute top-5 -left-[25px] size-2.5 rounded-full ring-4 ring-paper', 'bg-verified' => $record->evidence() === 'verified', 'bg-documented' => $record->evidence() === 'documented', 'bg-danger' => $record->evidence() === 'disputed', 'bg-line-strong' => $record->evidence() === 'self'])></span>
                            <article class="card p-4">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-semibold">{{ $record->title }}</p>
                                        <p class="mt-0.5 text-xs text-muted">
                                            <span class="num">{{ $record->performed_on->format('M j, Y') }}</span> ·
                                            <span class="num">{{ miles($record->mileage) }}</span> ·
                                            @if ($record->shop && $record->shop->is_listed && $record->evidence() === 'verified')
                                                <a href="{{ route('shops.show', $record->shop) }}" class="underline decoration-line-strong underline-offset-2 hover:text-ink">{{ $record->shop->name }}</a>
                                            @else
                                                {{ $record->provider_name ?: $record->provider_type->getLabel() }}
                                            @endif
                                            @if ($showCosts && $record->cost_cents)
                                                · <span class="num">{{ money($record->cost_cents) }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="badge-gray">{{ $record->category->getLabel() }}</span>
                                        <x-evidence :record="$record" />
                                    </div>
                                </div>
                                @if ($record->description)
                                    <p class="mt-2 text-sm text-ink-soft">{{ $record->description }}</p>
                                @endif
                                @if ($record->tasks)
                                    <p class="mt-2 flex flex-wrap gap-1">
                                        @foreach ($record->tasks as $task)
                                            <span class="rounded-md border border-line px-1.5 py-0.5 text-[11px] text-ink-soft">{{ $task }}</span>
                                        @endforeach
                                    </p>
                                @endif
                                @if ($record->isBackfilled() && $record->evidence() !== 'verified')
                                    <p class="mt-2 text-[11px] text-muted"><x-heroicon-m-clock class="inline size-3.5 align-[-2px]" /> Logged {{ $record->performed_on->diffInMonths($record->created_at) >= 1 ? (int) $record->performed_on->diffInMonths($record->created_at).' months' : (int) $record->performed_on->diffInDays($record->created_at).' days' }} after the work was done</p>
                                @endif
                                @if ($documentUrl && $record->relationLoaded('documents') && $record->documents->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($record->documents as $document)
                                            <a href="{{ $documentUrl($document) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-paper px-2.5 py-1 text-xs font-medium hover:border-ink">
                                                <x-heroicon-o-paper-clip class="size-3.5" /> {{ str($document->name)->limit(32) }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </article>
                        </li>
                    @endforeach
                </ol>
            </li>
        @endforeach
    </ol>
    @if ($limit && $records->count() > $limit)
        <p class="mt-4 text-sm text-muted">+ {{ $records->count() - $limit }} earlier records</p>
    @endif
@endif
