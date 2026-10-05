<form wire:submit="save" class="grid gap-6 lg:grid-cols-[1fr_320px]">
    <div class="space-y-6">
        @if ($locked)
            <div class="flex gap-3 rounded-2xl border border-verified/30 bg-verified-soft p-4 text-sm text-verified">
                <x-heroicon-s-check-badge class="size-5 shrink-0" />
                <p><strong>Confirmed by {{ $record->provider_name }}.</strong> The date, mileage, cost and work are locked so the confirmation stays meaningful. You can still add notes and attachments.</p>
            </div>
        @endif

        <section class="card card-pad">
            <h2 class="panel-title">The work</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-4">
                    <label class="label" for="title">What was done?</label>
                    <input id="title" wire:model="title" class="input" placeholder="e.g. 60,000-mile service" @disabled($locked)>
                    @error('title') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="category">Type</label>
                    <select id="category" wire:model="category" class="input" @disabled($locked)>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-3">
                    <label class="label" for="performed_on">Date</label>
                    <input id="performed_on" type="date" wire:model.live.debounce.400ms="performed_on" max="{{ now()->toDateString() }}" class="input" @disabled($locked)>
                    @error('performed_on') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-3">
                    <label class="label" for="mileage">Odometer</label>
                    <div class="relative">
                        <input id="mileage" type="number" min="0" wire:model.live.debounce.500ms="mileage" class="input num pr-10" @disabled($locked)>
                        <span class="absolute inset-y-0 right-3 grid place-items-center text-xs text-muted">mi</span>
                    </div>
                    @error('mileage') <p class="error">{{ $message }}</p> @enderror
                </div>
                @if ($this->mileageConflict && ! $locked)
                    <label class="flex gap-3 rounded-xl bg-warn-soft p-3 text-sm text-warn sm:col-span-6">
                        <input type="checkbox" wire:model="confirmLowerMileage" class="checkbox mt-0.5">
                        <span>This is lower than the <strong class="num">{{ number_format($this->mileageConflict['reading']) }} mi</strong> recorded on {{ $this->mileageConflict['date'] }}. Buyers will see this as the odometer going backwards. Tick to confirm it's correct.</span>
                    </label>
                @endif
                <div class="sm:col-span-6">
                    <label class="label" for="description">Notes <span class="font-normal text-muted">(optional)</span></label>
                    <textarea id="description" wire:model="description" rows="3" class="input" placeholder="Parts used, anything the shop flagged for next time…"></textarea>
                </div>
            </div>
        </section>

        <section class="card card-pad">
            <h2 class="panel-title">Who did it</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <div class="grid grid-cols-3 gap-2" role="radiogroup">
                        @foreach ($providers as $value => $label)
                            <label @class(['flex cursor-pointer items-center justify-center rounded-xl border px-3 py-2.5 text-center text-sm font-medium transition', 'border-ink bg-ink text-white' => $provider_type === $value, 'border-line-strong bg-surface hover:border-ink' => $provider_type !== $value, 'pointer-events-none opacity-60' => $locked])>
                                <input type="radio" wire:model.live="provider_type" value="{{ $value }}" class="sr-only" @disabled($locked)> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                @if ($provider_type !== 'diy' && $this->pickedShop && ! $locked)
                    <x-shop-picker class="sm:col-span-2" :picked="$this->pickedShop" :suggestions="collect()" />
                @elseif ($provider_type !== 'diy')
                    <div>
                        <label class="label" for="provider_name">Shop name</label>
                        <input id="provider_name" wire:model.live.debounce.300ms="provider_name" class="input" autocomplete="off" placeholder="e.g. Eastside Euro Specialists" @disabled($locked)>
                        @error('provider_name') <p class="error">{{ $message }}</p> @enderror
                        @unless ($locked)
                            <x-shop-picker class="mt-2" :picked="null" :suggestions="$this->shopSuggestions" />
                        @endunless
                    </div>
                    <div>
                        <label class="label" for="provider_email">Shop email <span class="font-normal text-muted">(for verification)</span></label>
                        <input id="provider_email" type="email" wire:model.live.debounce.500ms="provider_email" class="input" placeholder="service@shop.com" @disabled($locked)>
                        @error('provider_email') <p class="error">{{ $message }}</p> @enderror
                    </div>
                @endif
                @if ($provider_type !== 'diy')
                    @if (! $locked && ($provider_email || $this->pickedShop))
                        <label class="flex gap-3 rounded-xl border border-documented/20 bg-documented-soft p-3 text-sm text-documented sm:col-span-2">
                            <input type="checkbox" wire:model="requestVerification" class="checkbox mt-0.5">
                            <span><strong>Ask the shop to confirm this record.</strong> They get a one-click link — no account needed. Verified records carry the most weight with buyers.</span>
                        </label>
                    @endif
                @endif
            </div>
        </section>

        <section class="card card-pad">
            <div class="flex items-center justify-between gap-4">
                <h2 class="panel-title">Cost</h2>
                @unless ($locked)
                    <button type="button" wire:click="addItem" class="btn-ghost btn-sm"><x-heroicon-m-plus class="size-4" /> Itemise</button>
                @endunless
            </div>

            @if ($items)
                <div class="mt-4 space-y-2">
                    @foreach ($items as $i => $item)
                        <div class="grid grid-cols-[1fr_96px_110px_auto] items-start gap-2" wire:key="item-{{ $i }}">
                            <div>
                                <input wire:model="items.{{ $i }}.description" class="input" placeholder="e.g. Brake pads (front)" @disabled($locked)>
                                @error("items.$i.description") <p class="error">{{ $message }}</p> @enderror
                            </div>
                            <select wire:model="items.{{ $i }}.kind" class="input" @disabled($locked)>
                                <option value="part">Part</option>
                                <option value="labor">Labor</option>
                                <option value="fee">Fee</option>
                            </select>
                            <input wire:model.live.debounce.400ms="items.{{ $i }}.amount" inputmode="decimal" class="input num" placeholder="0.00" @disabled($locked)>
                            @unless ($locked)
                                <button type="button" wire:click="removeItem({{ $i }})" class="rounded-lg p-2.5 text-muted hover:bg-paper hover:text-danger" title="Remove"><x-heroicon-m-x-mark class="size-4" /></button>
                            @endunless
                        </div>
                    @endforeach
                    <p class="pt-2 text-right text-sm">Total <span class="num font-semibold">{{ money($this->itemsTotal) }}</span></p>
                </div>
            @else
                <div class="mt-4 max-w-xs">
                    <label class="label" for="cost">Total paid <span class="font-normal text-muted">(private unless you share it)</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 grid place-items-center text-sm text-muted">$</span>
                        <input id="cost" inputmode="decimal" wire:model="cost" class="input num pl-7" placeholder="0.00" @disabled($locked)>
                    </div>
                    @error('cost') <p class="error">{{ $message }}</p> @enderror
                </div>
            @endif
        </section>
    </div>

    <aside class="space-y-6">
        <section class="card card-pad">
            <h2 class="panel-title">Receipts</h2>
            <p class="mt-1 text-sm text-muted">A photo or PDF of the invoice turns this from a claim into evidence.</p>

            @foreach ($this->attachments as $doc)
                <div class="mt-3 flex items-center gap-2 rounded-xl border border-line p-2.5 text-sm" wire:key="doc-{{ $doc->id }}">
                    <x-heroicon-o-paper-clip class="size-4 shrink-0 text-muted" />
                    <a href="{{ route('documents.show', [$vehicle, $doc]) }}" target="_blank" class="min-w-0 flex-1 truncate hover:underline">{{ $doc->name }}</a>
                    <button type="button" wire:click="deleteAttachment({{ $doc->id }})" wire:confirm="Delete this attachment?" class="text-muted hover:text-danger" title="Delete"><x-heroicon-m-trash class="size-4" /></button>
                </div>
            @endforeach

            @foreach ($receipts as $i => $file)
                <div class="mt-3 flex items-center gap-2 rounded-xl border border-dashed border-line-strong p-2.5 text-sm" wire:key="upload-{{ $i }}">
                    <x-heroicon-o-arrow-up-tray class="size-4 shrink-0 text-muted" />
                    <span class="min-w-0 flex-1 truncate">{{ $file->getClientOriginalName() }}</span>
                    <button type="button" wire:click="removeUpload({{ $i }})" class="text-muted hover:text-danger"><x-heroicon-m-x-mark class="size-4" /></button>
                </div>
            @endforeach

            <label class="mt-3 flex cursor-pointer flex-col items-center gap-1 rounded-xl border-2 border-dashed border-line-strong bg-paper px-4 py-6 text-center text-sm hover:border-ink">
                <x-heroicon-o-document-arrow-up class="size-6 text-muted" />
                <span class="font-medium">Add receipt</span>
                <span class="text-xs text-muted">PDF, JPG, PNG · up to 10 MB</span>
                <input type="file" wire:model="receipts" multiple accept=".pdf,image/*" class="sr-only">
            </label>
            <div wire:loading wire:target="receipts" class="mt-2 text-xs text-muted">Uploading…</div>
            @error('receipts.*') <p class="error">{{ $message }}</p> @enderror
        </section>

        @if ($reminders->isNotEmpty())
            <section class="card card-pad">
                <h2 class="panel-title">Maintenance covered</h2>
                <p class="mt-1 text-sm text-muted">Ticked items reset their reminders.</p>
                <div class="mt-4 space-y-2">
                    @foreach ($reminders as $reminder)
                        <label class="flex items-center gap-3 text-sm" wire:key="rem-{{ $reminder->id }}">
                            <input type="checkbox" wire:model="reminderIds" value="{{ $reminder->id }}" class="checkbox" @disabled($locked)>
                            {{ $reminder->task }}
                        </label>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="flex flex-col gap-2">
            <button class="btn-accent w-full" wire:loading.attr="disabled" wire:target="save,receipts">{{ $record ? 'Save changes' : 'Add to passport' }}</button>
            <a href="{{ route('vehicles.history', $vehicle) }}" class="btn-ghost w-full">Cancel</a>
        </div>
    </aside>
</form>
