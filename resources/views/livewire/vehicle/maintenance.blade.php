<div class="grid gap-6 lg:grid-cols-[1fr_340px]">
    <div>
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="panel-title">Maintenance plan</h2>
                <p class="text-sm text-muted">Due by mileage or time, whichever comes first. Logging a record with the item ticked resets it.</p>
            </div>
            <button wire:click="create" class="btn-secondary shrink-0"><x-heroicon-m-plus class="size-4" /> Add item</button>
        </div>

        @if ($reminders->isEmpty())
            <x-empty class="mt-6" icon="heroicon-o-calendar-days" title="No maintenance items" text="Add the items from your owner's manual so you're reminded before they're due." />
        @else
            <ul class="mt-6 space-y-3">
                @foreach ($reminders as $reminder)
                    @php($status = $reminder->status($mileage))
                    <li class="card p-4" wire:key="reminder-{{ $reminder->id }}">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold">{{ $reminder->task }}</p>
                                    @switch($status)
                                        @case('overdue') <span class="badge-red">Overdue</span> @break
                                        @case('due_soon') <span class="badge-amber">Due soon</span> @break
                                        @case('ok') <span class="badge-green">OK</span> @break
                                        @default <span class="badge-gray">No record</span>
                                    @endswitch
                                </div>
                                <p class="mt-0.5 text-xs text-muted">
                                    Every {{ $reminder->intervalLabel() }}
                                    @if ($reminder->last_done_on) · last done {{ $reminder->last_done_on->format('M Y') }}@if ($reminder->last_done_mileage !== null) at <span class="num">{{ miles($reminder->last_done_mileage) }}</span>@endif @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('records.create', [$vehicle, 'reminder' => $reminder->id]) }}" class="btn-secondary btn-sm">Log it</a>
                                <button wire:click="edit({{ $reminder->id }})" class="btn-ghost btn-sm">Edit</button>
                            </div>
                        </div>
                        @if ($status !== 'unknown')
                            <div class="mt-3 flex items-center gap-3">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-paper-deep">
                                    <div @class(['h-full rounded-full', 'bg-danger' => $status === 'overdue', 'bg-warn' => $status === 'due_soon', 'bg-verified' => $status === 'ok']) style="width: {{ $reminder->progress($mileage) }}%"></div>
                                </div>
                                <span class="num shrink-0 text-xs text-muted">due {{ $reminder->dueLabel($mileage) }}</span>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <aside>
        @if ($editingId !== null)
            <form wire:submit="save" class="card card-pad sticky top-24">
                <h3 class="panel-title">{{ $editingId ? 'Edit item' : 'New item' }}</h3>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="label" for="task">Task</label>
                        <input id="task" wire:model="task" class="input" placeholder="e.g. Differential fluid">
                        @error('task') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label" for="interval_miles">Every (miles)</label>
                            <input id="interval_miles" type="number" wire:model="interval_miles" class="input num">
                        </div>
                        <div>
                            <label class="label" for="interval_months">Every (months)</label>
                            <input id="interval_months" type="number" wire:model="interval_months" class="input num">
                        </div>
                    </div>
                    @error('interval_miles') <p class="error -mt-2">{{ $message }}</p> @enderror
                    <div class="divider"></div>
                    <p class="text-xs text-muted">Last done — fill in if you know it but don't have a record yet.</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label" for="last_done_on">Date</label>
                            <input id="last_done_on" type="date" wire:model="last_done_on" class="input">
                            @error('last_done_on') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="last_done_mileage">Odometer</label>
                            <input id="last_done_mileage" type="number" wire:model="last_done_mileage" class="input num">
                            @error('last_done_mileage') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-between">
                    @if ($editingId)
                        <button type="button" wire:click="delete({{ $editingId }})" wire:confirm="Remove this item from the plan?" class="text-sm font-medium text-danger">Remove</button>
                    @else
                        <span></span>
                    @endif
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('editingId', null)" class="btn-ghost">Cancel</button>
                        <button class="btn-primary">Save</button>
                    </div>
                </div>
            </form>
        @else
            <div class="card card-pad">
                <p class="eyebrow">Why it matters</p>
                <p class="mt-2 text-sm text-ink-soft">Overdue items cost Passport Score points, and buyers can see whether the car was serviced on time. We'll email you before anything is due.</p>
            </div>
        @endif
    </aside>
</div>
