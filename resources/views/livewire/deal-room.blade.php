@php
    $status = $deal->status;
    $stage = $deal->stage();
    $pending = $deal->pendingOffer?->isOpen() ? $deal->pendingOffer : null;
    $vehicle = $deal->vehicle;
@endphp

<div wire:poll.15s.visible class="grid gap-6 lg:grid-cols-[1fr_380px]">
    {{-- Left: progress + conversation --}}
    <div class="min-w-0 space-y-6">
        <section class="card card-pad">
            <div class="flex flex-wrap items-center gap-4">
                <x-car-photo :vehicle="$vehicle" class="h-16 w-24 shrink-0 rounded-xl" />
                <div class="min-w-0 flex-1">
                    <p class="eyebrow">{{ $role === 'buyer' ? 'Buying from '.$other->publicName() : 'Selling to '.$other->publicName() }}</p>
                    <h2 class="display truncate text-xl"><a href="{{ route('listings.show', $deal->listing) }}" class="hover:underline">{{ $vehicle->title() }}</a></h2>
                    <p class="text-sm text-muted">Listed at <span class="num">{{ money($deal->listing->price_cents) }}</span>@if ($deal->agreed_price_cents) · agreed <span class="num font-semibold text-ink">{{ money($deal->agreed_price_cents) }}</span>@endif</p>
                </div>
                <span class="badge-{{ ['open' => 'blue', 'agreed' => 'amber', 'completed' => 'green', 'cancelled' => 'gray'][$status->value] }} text-xs">{{ $status->getLabel() }}</span>
            </div>

            @if ($status !== \App\Enums\DealStatus::Cancelled)
                <ol class="mt-6 grid grid-cols-5 gap-1.5 text-[11px] font-medium">
                    @foreach (['Talk & offer', 'Price agreed', 'Inspected', 'Handover', 'Transferred'] as $i => $label)
                        <li>
                            <div @class(['h-1.5 rounded-full', 'bg-accent' => $stage >= $i, 'bg-paper-deep' => $stage < $i])></div>
                            <p @class(['mt-1.5 truncate', 'text-ink' => $stage >= $i, 'text-muted' => $stage < $i])>{{ $label }}</p>
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="mt-4 rounded-xl bg-paper p-3 text-sm text-ink-soft">This deal was cancelled {{ $deal->cancelled_at->diffForHumans() }}{{ $deal->cancel_reason ? ': “'.$deal->cancel_reason.'”' : '.' }}</p>
            @endif
        </section>

        @if ($status === \App\Enums\DealStatus::Completed)
            <section class="card card-pad border-verified/30 bg-verified-soft text-center">
                <x-heroicon-s-check-badge class="mx-auto size-12 text-verified" />
                <h3 class="display mt-3 text-2xl">Sale complete</h3>
                <p class="mx-auto mt-1 max-w-md text-sm text-ink-soft">{{ $role === 'buyer' ? 'The car — and every record, receipt and reading — is now in your garage as Owner '.$vehicle->ownerships()->max('owner_number').'.' : 'The passport has moved to the buyer\'s garage. Your running costs and personal documents stayed with you.' }}</p>
                <div class="mt-5 flex justify-center gap-2">
                    @if ($role === 'buyer')
                        <a href="{{ route('vehicles.show', $vehicle) }}" class="btn-primary">Open in my garage</a>
                    @endif
                    <a href="{{ route('deals.bill-of-sale', $deal) }}" class="btn-secondary">Bill of sale (PDF)</a>
                </div>
            </section>
        @endif

        {{-- Conversation --}}
        <section class="card flex flex-col">
            <div class="border-b border-line px-5 py-4"><h3 class="panel-title">Conversation</h3></div>
            <ol class="flex max-h-[560px] flex-col gap-3 overflow-y-auto px-5 py-5" x-data x-init="$el.scrollTop = $el.scrollHeight">
                @foreach ($messages as $message)
                    @if ($message->isSystem())
                        <li class="mx-auto max-w-md rounded-full bg-paper px-3 py-1 text-center text-xs text-ink-soft" wire:key="m-{{ $message->id }}">{{ $message->body }}</li>
                    @else
                        @php($mine = $message->user_id === $me->id)
                        @php($severity = \App\Services\ScamShield::highestSeverity($message->risk_flags))
                        <li @class(['flex max-w-[85%] flex-col', 'ml-auto items-end' => $mine]) wire:key="m-{{ $message->id }}">
                            <div @class(['rounded-2xl px-4 py-2.5 text-sm whitespace-pre-line', 'rounded-br-md bg-ink text-white' => $mine, 'rounded-bl-md bg-paper-deep' => ! $mine])>{{ $message->body }}</div>
                            <span class="mt-1 text-[11px] text-muted">{{ $mine ? 'You' : $message->user?->publicName() }} · {{ $message->created_at->format('M j, g:i a') }}</span>
                            @if ($severity && ! $mine)
                                <div @class(['mt-2 rounded-xl border p-3 text-xs', 'border-danger/30 bg-danger-soft text-danger' => $severity === 'high', 'border-warn/30 bg-warn-soft text-warn' => $severity !== 'high'])>
                                    @foreach ($message->risk_flags as $flag)
                                        <p @class(['font-semibold', 'mt-2' => ! $loop->first])><x-heroicon-s-shield-exclamation class="inline size-4 align-[-3px]" /> {{ $flag['label'] }}</p>
                                        <p class="mt-0.5">{{ $flag['advice'] }}</p>
                                    @endforeach
                                </div>
                            @elseif ($severity === 'high' && $mine)
                                <span class="mt-1 text-[11px] text-danger">Flagged for the other person's safety</span>
                            @endif
                        </li>
                    @endif
                @endforeach
            </ol>
            @if ($status->isActive())
                <form wire:submit="send" class="flex gap-2 border-t border-line p-3">
                    <label for="body" class="sr-only">Message</label>
                    <textarea id="body" wire:model="body" rows="1" class="input min-h-11 flex-1 resize-none" placeholder="Write a message…" @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $wire.send() }"></textarea>
                    <button class="btn-primary" aria-label="Send"><x-heroicon-m-paper-airplane class="size-4" /></button>
                </form>
                @error('body') <p class="error px-4 pb-3">{{ $message }}</p> @enderror
            @endif
        </section>

        {{-- Inspection --}}
        @if ($deal->inspection && in_array($status, [\App\Enums\DealStatus::Agreed, \App\Enums\DealStatus::Completed]))
            @php($inspection = $deal->inspection)
            <section class="card card-pad">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="panel-title">Pre-purchase inspection</h3>
                        <p class="text-sm text-muted">{{ $role === 'buyer' ? 'Go through it yourself or hand your phone to your mechanic. Results are shared with the seller.' : 'The buyer records the inspection here. You\'ll see the results as they go.' }}</p>
                    </div>
                    @if ($inspection->completed_at)
                        <span class="badge-green">Completed {{ $inspection->completed_at->format('M j') }}</span>
                    @endif
                </div>

                @if ($role === 'buyer' && $status === \App\Enums\DealStatus::Agreed)
                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div><label class="label" for="scheduled_for">When</label><input id="scheduled_for" type="datetime-local" wire:model="scheduled_for" class="input"></div>
                        <div><label class="label" for="location">Where</label><input id="location" wire:model="location" class="input" placeholder="Shop or address"></div>
                        <div><label class="label" for="inspector">Inspector</label><input id="inspector" wire:model="inspector" class="input" placeholder="optional"></div>
                    </div>
                @elseif ($inspection->scheduled_for || $inspection->location)
                    <p class="mt-4 text-sm"><x-heroicon-m-calendar class="inline size-4 align-[-3px] text-muted" /> {{ $inspection->scheduled_for?->format('D, M j \a\t g:i a') }} {{ $inspection->location ? '· '.$inspection->location : '' }} {{ $inspection->inspector ? '· '.$inspection->inspector : '' }}</p>
                @endif

                <div class="mt-6 space-y-6">
                    @foreach ($checklist as $group => $items)
                        <div>
                            <p class="eyebrow mb-2">{{ $group }}</p>
                            <ul class="divide-y divide-line rounded-xl border border-line">
                                @foreach ($items as $key => $label)
                                    @php($result = $inspection->resultFor($key))
                                    <li class="flex flex-col gap-2 p-3 sm:flex-row sm:items-center" wire:key="insp-{{ $key }}">
                                        <span class="flex-1 text-sm">{{ $label }}</span>
                                        @if ($role === 'buyer' && $status === \App\Enums\DealStatus::Agreed)
                                            <div class="flex gap-1">
                                                @foreach (['pass' => 'Good', 'attention' => 'Watch', 'fail' => 'Problem'] as $value => $text)
                                                    <label @class(['cursor-pointer rounded-lg border px-2.5 py-1 text-xs font-semibold', 'border-verified bg-verified text-white' => ($results[$key]['result'] ?? '') === $value && $value === 'pass', 'border-warn bg-warn text-white' => ($results[$key]['result'] ?? '') === $value && $value === 'attention', 'border-danger bg-danger text-white' => ($results[$key]['result'] ?? '') === $value && $value === 'fail', 'border-line-strong' => ($results[$key]['result'] ?? '') !== $value])>
                                                        <input type="radio" class="sr-only" wire:model.live="results.{{ $key }}.result" value="{{ $value }}"> {{ $text }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="badge-{{ ['pass' => 'green', 'attention' => 'amber', 'fail' => 'red', 'not_checked' => 'gray'][$result->value] }}">{{ $result->getLabel() }}</span>
                                        @endif
                                    </li>
                                    @if (! empty($inspection->results[$key]['note']) && ! ($role === 'buyer' && $status === \App\Enums\DealStatus::Agreed))
                                        <li class="px-3 pb-3 text-xs text-muted">“{{ $inspection->results[$key]['note'] }}”</li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>

                @if ($role === 'buyer' && $status === \App\Enums\DealStatus::Agreed)
                    <div class="mt-5">
                        <label class="label" for="summary">Overall notes</label>
                        <textarea id="summary" wire:model="summary" rows="3" class="input" placeholder="What the inspector said, anything to fix before handover…"></textarea>
                    </div>
                    <div class="mt-5 flex flex-wrap justify-end gap-2">
                        <button wire:click="saveInspection" class="btn-secondary">Save progress</button>
                        <button wire:click="saveInspection(true)" class="btn-primary">{{ $inspection->completed_at ? 'Update results' : 'Mark inspection complete' }}</button>
                    </div>
                @elseif ($inspection->summary)
                    <p class="mt-5 rounded-xl bg-paper p-3 text-sm text-ink-soft">{{ $inspection->summary }}</p>
                @endif
            </section>
        @endif
    </div>

    {{-- Right: offers / handover --}}
    <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
        @foreach (['offer', 'handover', 'cancel', 'cancelReason', 'note', 'results.*'] as $key)
            @error($key)
                <p class="rounded-xl bg-danger-soft p-3 text-sm font-medium text-danger" role="alert">{{ $message }}</p>
            @enderror
        @endforeach
        @if ($status === \App\Enums\DealStatus::Open)
            <section class="card card-pad">
                <h3 class="panel-title">Price</h3>
                @if ($pending)
                    <div class="mt-4 rounded-2xl border-2 border-dashed border-line-strong p-4 text-center">
                        <p class="eyebrow">{{ $pending->user_id === $me->id ? 'Your offer' : $other->publicName().'\'s offer' }}</p>
                        <p class="num mt-1 text-3xl font-semibold">{{ money($pending->amount_cents) }}</p>
                        @if ($pending->note)<p class="mt-1 text-sm text-ink-soft">“{{ $pending->note }}”</p>@endif
                        <p class="mt-2 text-xs text-muted">Open until {{ $pending->expires_at->format('M j, g:i a') }}</p>
                        @if ($pending->user_id !== $me->id && $deal->listing->status === \App\Enums\ListingStatus::Active)
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <button wire:click="respond({{ $pending->id }}, false)" class="btn-secondary">Decline</button>
                                <button wire:click="respond({{ $pending->id }}, true)" wire:confirm="Accept {{ money($pending->amount_cents) }}? The listing will be marked as sale agreed." class="btn-accent">Accept</button>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($deal->listing->status === \App\Enums\ListingStatus::Active)
                    <form wire:submit="makeOffer" class="mt-4 space-y-3">
                        <label class="label" for="amount">{{ $pending && $pending->user_id !== $me->id ? 'Or counter with' : ($pending ? 'Change your offer' : ($role === 'buyer' ? 'Make an offer' : 'Propose a price')) }}</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 grid place-items-center text-sm text-muted">$</span>
                            <input id="amount" inputmode="decimal" wire:model="amount" class="input num pl-7" placeholder="{{ number_format($deal->listing->price_cents / 100) }}">
                        </div>
                        @error('amount') <p class="error">{{ $message }}</p> @enderror
                        <input wire:model="note" class="input" placeholder="Add a note (optional)">
                        <button class="btn-primary w-full">Send offer</button>
                    </form>
                @else
                    <p class="mt-4 rounded-xl bg-warn-soft p-3 text-sm text-warn">
                        @switch($deal->listing->status)
                            @case(\App\Enums\ListingStatus::Pending) The seller has agreed a sale with someone else. You can keep talking in case it falls through. @break
                            @case(\App\Enums\ListingStatus::Withdrawn) The seller has taken the car off the market. @break
                            @case(\App\Enums\ListingStatus::Removed) This listing was removed by our trust & safety team. @break
                            @default This car is no longer for sale.
                        @endswitch
                    </p>
                @endif

                @if ($offers->count() > ($pending ? 1 : 0))
                    <details class="mt-4 text-sm">
                        <summary class="cursor-pointer text-muted">Offer history</summary>
                        <ul class="mt-2 space-y-1">
                            @foreach ($offers as $offer)
                                <li class="flex justify-between gap-3 text-xs"><span>{{ $offer->user_id === $me->id ? 'You' : $offer->user->publicName() }} · {{ $offer->created_at->format('M j') }}</span><span><span class="num">{{ money($offer->amount_cents) }}</span> · {{ $offer->status->getLabel() }}</span></li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </section>
        @endif

        @if (in_array($status, [\App\Enums\DealStatus::Agreed, \App\Enums\DealStatus::Completed]))
            <section class="card card-pad">
                <h3 class="panel-title">Handover</h3>
                <p class="mt-1 text-sm text-muted">Each person ticks their own steps. Both confirm at the end.</p>
                <ul class="mt-4 space-y-2">
                    @foreach ($deal->handoverItems() as $key => $item)
                        @php($mineToTick = $item['by'] === $role && $status === \App\Enums\DealStatus::Agreed)
                        <li wire:key="h-{{ $key }}">
                            <button type="button" @if ($mineToTick) wire:click="toggle('{{ $key }}')" @else disabled @endif
                                @class(['flex w-full items-start gap-3 rounded-xl border p-3 text-left text-sm transition', 'border-verified/30 bg-verified-soft' => $item['done_at'], 'border-line hover:border-ink' => ! $item['done_at'] && $mineToTick, 'border-line opacity-80' => ! $item['done_at'] && ! $mineToTick])>
                                <span @class(['mt-0.5 grid size-5 shrink-0 place-items-center rounded-md border', 'border-verified bg-verified text-white' => $item['done_at'], 'border-line-strong' => ! $item['done_at']])>
                                    @if ($item['done_at']) <x-heroicon-m-check class="size-3.5" /> @endif
                                </span>
                                <span class="flex-1">{{ $item['label'] }}@unless ($item['required']) <span class="text-muted">(optional)</span>@endunless
                                    <span class="block text-xs text-muted">{{ $item['by'] === $role ? 'You' : ucfirst($item['by']) }}</span></span>
                            </button>
                        </li>
                    @endforeach
                </ul>

                @if ($status === \App\Enums\DealStatus::Agreed)
                    @php($myConfirm = $role === 'buyer' ? $deal->buyer_confirmed_at : $deal->seller_confirmed_at)
                    @php($theirConfirm = $role === 'buyer' ? $deal->seller_confirmed_at : $deal->buyer_confirmed_at)
                    <div class="divider my-5"></div>
                    @if (! $deal->handoverComplete())
                        <p class="text-sm text-muted">Finish the required steps to complete the sale.</p>
                    @elseif ($myConfirm)
                        <p class="text-sm text-verified">You confirmed. {{ $theirConfirm ? '' : 'Waiting for '.$other->publicName().'.' }}</p>
                    @else
                        <form wire:submit="confirm" class="space-y-3">
                            @if ($role === 'seller')
                                <div>
                                    <label class="label" for="saleMileage">Odometer at handover</label>
                                    <input id="saleMileage" type="number" wire:model="saleMileage" class="input num">
                                    @error('sale_mileage') <p class="error">{{ $message }}</p> @enderror
                                </div>
                                <fieldset>
                                    <legend class="label">Odometer certification</legend>
                                    <p class="text-xs text-muted">Federal law makes you certify this reading on the odometer disclosure. Pick what's true.</p>
                                    @if ($rollbacks)
                                        <p class="mt-2 rounded-xl border border-warn/30 bg-warn-soft p-3 text-xs text-ink">This car's passport shows the odometer going backwards ({{ miles($rollbacks[0]['reading']) }} on {{ \Illuminate\Support\Carbon::parse($rollbacks[0]['date'])->format('M j, Y') }}, after {{ miles($rollbacks[0]['previous_max']) }}). If that was a typo, fix the record. If the odometer was replaced or reset, the reading isn't the actual mileage.</p>
                                    @endif
                                    <div class="mt-2 space-y-2">
                                        @foreach (\App\Enums\OdometerStatus::cases() as $option)
                                            <label class="flex items-start gap-2 rounded-xl border border-line p-3 text-sm has-[:checked]:border-ink" wire:key="os-{{ $option->value }}">
                                                <input type="radio" wire:model="odometerStatus" value="{{ $option->value }}" class="mt-0.5">
                                                <span><span class="font-medium">{{ $option->label() }}</span><span class="block text-xs text-muted">{{ $option->explanation() }}</span></span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('odometer_status') <p class="error">{{ $message }}</p> @enderror
                                </fieldset>
                            @endif
                            @if ($role === 'buyer' && $deal->seller_confirmed_at && $deal->sale_mileage)
                                <p @class(['rounded-xl p-3 text-sm', 'bg-paper' => $deal->odometer_status !== \App\Enums\OdometerStatus::NotActual, 'border border-danger/30 bg-danger-soft' => $deal->odometer_status === \App\Enums\OdometerStatus::NotActual])>The seller recorded <strong class="num">{{ miles($deal->sale_mileage) }}</strong> at handover and certified it as <strong>{{ Str::lower($deal->odometer_status?->label() ?? 'actual mileage') }}</strong>. Check it against the dashboard before you confirm.</p>
                            @endif
                            <p class="text-xs text-muted">{{ $theirConfirm ? $other->publicName().' has confirmed.' : '' }} When you both confirm, the passport transfers to {{ $role === 'buyer' ? 'you' : $other->publicName() }}.</p>
                            <button class="btn-accent w-full">Confirm sale complete</button>
                        </form>
                    @endif
                    @error('confirm') <p class="error">{{ $message }}</p> @enderror
                @endif
            </section>
        @endif

        @if (in_array($status, [\App\Enums\DealStatus::Agreed, \App\Enums\DealStatus::Completed]))
            @php($exemption = \App\Support\OdometerDisclosure::exemption($vehicle, $deal->completed_at))
            <section class="card card-pad">
                <h3 class="panel-title">Paperwork</h3>
                <p class="mt-1 text-sm text-muted">Print these, sign them together at handover, and each keep a copy.</p>
                <ul class="mt-4 space-y-2 text-sm">
                    <li class="flex items-center justify-between gap-3 rounded-xl border border-line p-3">
                        <span><span class="font-medium">Bill of sale</span><span class="block text-xs text-muted">Price, date, both parties, sold as is.</span></span>
                        <a href="{{ route('deals.bill-of-sale', $deal) }}" class="btn-secondary btn-sm shrink-0"><x-heroicon-m-document-arrow-down class="size-4" /> PDF</a>
                    </li>
                    <li class="flex items-center justify-between gap-3 rounded-xl border border-line p-3">
                        <span><span class="font-medium">Odometer disclosure</span>
                            <span class="block text-xs text-muted">{{ $exemption ?? ($deal->sale_mileage ? 'Filled in with the seller\'s handover reading and certification.' : 'Required by federal law for this car. It fills in when the seller confirms the handover reading.') }}</span></span>
                        @unless ($exemption)
                            <a href="{{ route('deals.odometer-disclosure', $deal) }}" class="btn-secondary btn-sm shrink-0"><x-heroicon-m-document-arrow-down class="size-4" /> PDF</a>
                        @endunless
                    </li>
                </ul>
                <h4 class="mt-5 text-xs font-semibold uppercase tracking-wide text-muted">At handover</h4>
                <ul class="mt-2 list-disc space-y-1.5 pl-5 text-sm text-ink-soft">
                    <li>The seller signs the title over to the buyer and fills in its odometer section with the same reading. If a lender holds the title, the loan is paid off and the title released first.</li>
                    <li>The buyer has insurance in place before driving away.</li>
                    <li>Plates, temporary tags and any notice-of-sale or release-of-liability filing for the seller vary by state.</li>
                    <li>The buyer titles and registers the car, and pays any sales tax, with their state's DMV. Deadlines vary by state.</li>
                </ul>
                <p class="mt-3 text-xs text-muted">Check your state's DMV (motor vehicle agency) website for its forms and deadlines before you meet.</p>
            </section>
        @endif

        @if ($status->isActive())
            <section class="rounded-2xl border border-line bg-paper-deep/60 p-5 text-sm">
                <p class="flex items-center gap-1.5 font-semibold"><x-heroicon-s-shield-check class="size-4 text-verified" /> Scam shield is on</p>
                <p class="mt-1 text-ink-soft">We flag messages that mention gift cards, wire transfers, "shipping agents" or verification codes. We never hold money — pay in person at a bank.</p>
            </section>

            @if ($cancelling)
                <form wire:submit="cancel" class="card card-pad space-y-3">
                    <label class="label" for="cancelReason">Why are you cancelling? <span class="font-normal text-muted">(shared)</span></label>
                    <input id="cancelReason" wire:model="cancelReason" class="input">
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="$set('cancelling', false)" class="btn-ghost btn-sm">Keep deal</button>
                        <button class="btn-danger btn-sm">Cancel deal</button>
                    </div>
                </form>
            @else
                <button wire:click="$set('cancelling', true)" class="w-full text-center text-sm text-muted hover:text-danger">Cancel this deal</button>
            @endif
        @endif
    </aside>
</div>
