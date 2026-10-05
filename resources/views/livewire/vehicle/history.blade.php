<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-2">
            @foreach (['' => 'All', 'verified' => 'Shop verified', 'documented' => 'With receipt', 'self' => 'Self-reported', 'disputed' => 'Disputed'] as $value => $label)
                @if ($value === '' || ($counts[$value] ?? 0) > 0)
                    <button wire:click="$set('evidence', '{{ $value }}')" @class(['rounded-full border px-3 py-1.5 text-xs font-semibold transition', 'border-ink bg-ink text-white' => $evidence === $value, 'border-line-strong bg-surface hover:border-ink' => $evidence !== $value])>
                        {{ $label }} <span class="num ml-0.5 opacity-60">{{ $value === '' ? $total : $counts[$value] }}</span>
                    </button>
                @endif
            @endforeach
        </div>
        <div class="flex items-center gap-2">
            <select wire:model.live="category" class="input w-auto py-2" aria-label="Filter by type">
                <option value="">All types</option>
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <a href="{{ route('records.create', $vehicle) }}" class="btn-accent"><x-heroicon-m-plus class="size-4" /> Log work</a>
        </div>
    </div>

    @if ($total === 0)
        <x-empty class="mt-6" icon="heroicon-o-wrench-screwdriver" title="Start with your last service" text="Dig out the most recent invoice and log it. Older records can follow — every year you fill in raises the Passport Score.">
            <a href="{{ route('records.create', $vehicle) }}" class="btn-primary">Log the first record</a>
        </x-empty>
    @elseif ($records->isEmpty())
        <x-empty class="mt-6" icon="heroicon-o-funnel" title="Nothing matches these filters" />
    @else
        <ol class="mt-6 space-y-3">
            @foreach ($records as $record)
                @php($latest = $record->verifications->first())
                @php($mine = $record->isFromOwnership($currentOwnershipId))
                <li class="card p-4 sm:p-5" wire:key="record-{{ $record->id }}">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <div class="w-28 shrink-0">
                            <p class="num text-sm font-semibold">{{ $record->performed_on->format('M j, Y') }}</p>
                            <p class="num text-xs text-muted">{{ miles($record->mileage) }}</p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold">{{ $record->title }}</h3>
                                <span class="badge-gray">{{ $record->category->getLabel() }}</span>
                                <x-evidence :record="$record" />
                            </div>
                            <p class="mt-1 text-sm text-muted">
                                {{ $record->provider_name ?: $record->provider_type->getLabel() }}
                                @if ($mine && $record->cost_cents) · <span class="num">{{ money($record->cost_cents, true) }}</span>@endif
                                @if ($record->ownership_id !== $currentOwnershipId && $record->ownership) · <span class="text-ink-soft">{{ $record->ownership->label() }}</span>@endif
                            </p>
                            @if ($record->description)
                                <p class="mt-2 text-sm text-ink-soft">{{ $record->description }}</p>
                            @endif
                            @if ($mine && $record->line_items)
                                <ul class="mt-2 max-w-md space-y-0.5 text-xs text-muted">
                                    @foreach ($record->line_items as $item)
                                        <li class="flex justify-between gap-4"><span>{{ $item['description'] }} <span class="opacity-60">· {{ $item['kind'] }}</span></span><span class="num">{{ money($item['amount_cents'], true) }}</span></li>
                                    @endforeach
                                </ul>
                            @endif
                            @if ($record->documents->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($record->documents as $doc)
                                        <a href="{{ route('documents.show', [$vehicle, $doc]) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-paper px-2.5 py-1 text-xs font-medium hover:border-ink"><x-heroicon-o-paper-clip class="size-3.5" /> {{ str($doc->name)->limit(28) }}</a>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Verification status --}}
                            @if ($record->pendingVerification)
                                <p class="mt-3 flex flex-wrap items-center gap-2 text-xs text-warn">
                                    <x-heroicon-m-clock class="size-4" /> Waiting for {{ $record->pendingVerification->shop_name }} to confirm (link expires {{ $record->pendingVerification->expires_at->diffForHumans() }})
                                    <button wire:click="cancelVerification({{ $record->id }})" class="font-semibold underline">Cancel request</button>
                                </p>
                            @elseif ($record->evidence() === 'disputed' && $latest?->response_note)
                                <p class="mt-3 rounded-xl bg-danger-soft p-3 text-xs text-danger"><strong>{{ $latest->shop_name }}:</strong> “{{ $latest->response_note }}”</p>
                            @elseif ($record->evidence() === 'verified' && $latest?->responder_name)
                                <p class="mt-3 text-xs text-verified">Confirmed by {{ $latest->responder_name }} at {{ $latest->shop_name }} on {{ $latest->responded_at->format('M j, Y') }}</p>
                            @endif
                        </div>
                        @if ($mine)
                        <div class="flex shrink-0 items-center gap-1 sm:flex-col sm:items-end">
                            @if ($record->canRequestVerification() && ! $record->pendingVerification)
                                <button wire:click="startVerification({{ $record->id }})" class="btn-secondary btn-sm"><x-heroicon-m-check-badge class="size-4" /> Ask shop to verify</button>
                            @endif
                            <div class="flex gap-1">
                                <a href="{{ route('records.edit', [$vehicle, $record]) }}" class="btn-ghost btn-sm">Edit</a>
                                <button wire:click="delete({{ $record->id }})" wire:confirm="Delete this record and its receipts? This can't be undone." class="btn-ghost btn-sm text-danger hover:bg-danger-soft hover:text-danger">Delete</button>
                            </div>
                        </div>
                        @else
                            <span class="badge-gray shrink-0 self-start" title="Logged by a previous owner — part of the car's history, so it can't be changed">Read-only</span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
        <p class="mt-4 text-right text-sm text-muted">Your recorded spend <span class="num font-semibold text-ink">{{ money($spend) }}</span></p>
    @endif

    {{-- Verification request dialog --}}
    @if ($verifyingId)
        <div class="fixed inset-0 z-50 grid place-items-center bg-ink/40 p-4 backdrop-blur-sm" wire:click.self="$set('verifyingId', null)">
            <form wire:submit="sendVerification" class="card w-full max-w-md p-6 shadow-2xl">
                <h3 class="display text-xl">Ask the shop to verify</h3>
                <p class="mt-1 text-sm text-ink-soft">We'll email them the record with a one-click confirm link. They don't need an account, and they can flag anything that doesn't match their invoice.</p>
                <div class="mt-5 space-y-4">
                    @if ($this->pickedShop)
                        <x-shop-picker :picked="$this->pickedShop" :suggestions="collect()" />
                    @else
                        <div>
                            <label class="label" for="shopName">Shop name</label>
                            <input id="shopName" wire:model.live.debounce.300ms="shopName" class="input" autocomplete="off" placeholder="Start typing to find shops that already verify">
                            @error('shopName') <p class="error">{{ $message }}</p> @enderror
                            <x-shop-picker class="mt-2" :picked="null" :suggestions="$this->shopSuggestions" />
                        </div>
                        <div>
                            <label class="label" for="shopEmail">Shop email</label>
                            <input id="shopEmail" type="email" wire:model="shopEmail" class="input" placeholder="service@shop.com">
                            @error('shopEmail') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="$set('verifyingId', null)" class="btn-ghost">Cancel</button>
                    <button class="btn-primary">Send request</button>
                </div>
            </form>
        </div>
    @endif
</div>
