<div class="mx-auto max-w-3xl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="panel-title">Safety recalls</h2>
            <p class="text-sm text-muted">From NHTSA for the {{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}. {{ $checkedAt ? 'Last checked '.$checkedAt->diffForHumans().'.' : 'Not checked yet.' }} We re-check weekly and email you about new ones.</p>
        </div>
        <button wire:click="check" class="btn-secondary" wire:loading.attr="disabled">
            <x-heroicon-m-arrow-path class="size-4" wire:loading.class="animate-spin" wire:target="check" /> Check now
        </button>
    </div>

    @if ($open->isEmpty() && $resolved->isEmpty())
        <x-empty class="mt-6" icon="heroicon-o-shield-check" title="No recalls on file" text="Good news. Note that recalls are published by model, so it's worth checking any open one against your exact VIN at nhtsa.gov/recalls." />
    @endif

    @foreach ($open as $recall)
        <article class="card mt-4 border-danger/30 p-5" wire:key="recall-{{ $recall->id }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <span class="badge-red">Open</span>
                    <h3 class="mt-2 font-semibold">{{ $recall->component }}</h3>
                    <p class="vin text-xs text-muted">NHTSA {{ $recall->campaign_number }}{{ $recall->reported_on ? ' · '.$recall->reported_on->format('M j, Y') : '' }}</p>
                </div>
            </div>
            <div class="mt-3 space-y-2 text-sm text-ink-soft">
                <p>{{ $recall->summary }}</p>
                @if ($recall->consequence)<p><strong class="text-ink">Risk:</strong> {{ $recall->consequence }}</p>@endif
                @if ($recall->remedy)<p><strong class="text-ink">Fix:</strong> {{ $recall->remedy }}</p>@endif
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-2 rounded-xl bg-paper p-3">
                <span class="text-sm">Fixed already?</span>
                <select wire:model="fixRecord.{{ $recall->id }}" class="input w-auto flex-1 py-1.5 text-xs">
                    <option value="">Link the record (optional)</option>
                    @foreach ($records as $r)
                        <option value="{{ $r->id }}">{{ $r->performed_on->format('M Y') }} — {{ str($r->title)->limit(40) }}</option>
                    @endforeach
                </select>
                <button wire:click="resolve({{ $recall->id }})" class="btn-primary btn-sm">Mark fixed</button>
            </div>
        </article>
    @endforeach

    @if ($resolved->isNotEmpty())
        <h3 class="eyebrow mt-10 mb-3">Fixed</h3>
        <ul class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
            @foreach ($resolved as $recall)
                <li class="flex flex-wrap items-center gap-3 p-4" wire:key="recall-{{ $recall->id }}">
                    <x-heroicon-s-check-circle class="size-5 text-verified" />
                    <div class="min-w-0 flex-1 text-sm">
                        <p class="font-medium">{{ $recall->component }}</p>
                        <p class="text-xs text-muted">Fixed {{ $recall->resolved_at->format('M j, Y') }}{{ $recall->record ? ' · '.$recall->record->title : '' }}</p>
                    </div>
                    <button wire:click="reopen({{ $recall->id }})" class="btn-ghost btn-sm">Reopen</button>
                </li>
            @endforeach
        </ul>
    @endif
</div>
