@php($record = $verification->record)
<x-layouts.site title="Confirm a service record" robots="noindex">
    <div class="container-x max-w-2xl py-12">
        <p class="eyebrow">Shop verification request</p>
        <h1 class="display mt-2 text-3xl">Hello {{ $verification->shop_name }}</h1>

        @if (! $verification->isAnswerable())
            <div class="card card-pad mt-8 text-center">
                <p class="font-semibold">This request has already been answered or has expired.</p>
                <p class="mt-1 text-sm text-muted">Thank you — there's nothing more to do.</p>
            </div>
        @else
            <p class="mt-3 text-ink-soft">{{ $verification->requester?->name }} says your shop did this work on their {{ $record->vehicle->title() }}. Does it match your records?</p>

            <div class="card card-pad mt-8">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><dt class="eyebrow">Work</dt><dd class="mt-1 text-lg font-semibold">{{ $record->title }}</dd>@if ($record->description)<dd class="text-sm text-ink-soft">{{ $record->description }}</dd>@endif</div>
                    <div><dt class="eyebrow">Date</dt><dd class="num mt-1 font-medium">{{ $record->performed_on->format('F j, Y') }}</dd></div>
                    <div><dt class="eyebrow">Odometer</dt><dd class="num mt-1 font-medium">{{ miles($record->mileage) }}</dd></div>
                    <div><dt class="eyebrow">Vehicle</dt><dd class="mt-1 font-medium">{{ $record->vehicle->fullTitle() }}</dd></div>
                    <div><dt class="eyebrow">VIN</dt><dd class="vin mt-1 font-medium">{{ $record->vehicle->vin }}</dd></div>
                    @if ($record->cost_cents)
                        <div><dt class="eyebrow">Invoice total</dt><dd class="num mt-1 font-medium">{{ money($record->cost_cents, true) }}</dd></div>
                    @endif
                    @if ($record->documents->isNotEmpty())
                        <div><dt class="eyebrow">Attached</dt><dd class="mt-1 text-sm">{{ $record->documents->count() }} receipt(s) on file</dd></div>
                    @endif
                </dl>
            </div>

            <form method="POST" action="{{ $verification->signedUrl() }}" class="card card-pad mt-6" x-data="{ decision: '{{ old('decision', 'confirm') }}' }">
                @csrf
                <fieldset class="grid gap-3 sm:grid-cols-2">
                    <legend class="sr-only">Your answer</legend>
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-4" :class="decision === 'confirm' ? 'border-verified bg-verified-soft' : 'border-line-strong'">
                        <input type="radio" name="decision" value="confirm" x-model="decision" class="text-verified focus:ring-verified"> <span class="font-semibold">Yes, we did this work</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-4" :class="decision === 'dispute' ? 'border-danger bg-danger-soft' : 'border-line-strong'">
                        <input type="radio" name="decision" value="dispute" x-model="decision" class="text-danger focus:ring-danger"> <span class="font-semibold">Something doesn't match</span>
                    </label>
                </fieldset>
                <div class="mt-5 grid gap-4">
                    @if ($verification->shop && ! $verification->shop->hasConfirmedName())
                        <div x-show="decision === 'confirm'">
                            <label class="label" for="business_name">Your shop's name</label>
                            <input id="business_name" name="business_name" value="{{ old('business_name', $verification->shop->name) }}" class="input" maxlength="120">
                            <p class="hint">The customer typed this in. Correct it if needed: it's how your shop appears on confirmed records and in our directory.</p>
                            @error('business_name') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    @elseif ($verification->shop)
                        <p class="text-sm text-ink-soft">Answering as <strong>{{ $verification->shop->name }}</strong>.</p>
                    @endif
                    <div>
                        <label class="label" for="responder_name">Your name</label>
                        <input id="responder_name" name="responder_name" value="{{ old('responder_name') }}" class="input" required>
                        @error('responder_name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="response_note"><span x-text="decision === 'dispute' ? 'What doesn\'t match?' : 'Note for the owner (optional)'"></span></label>
                        <textarea id="response_note" name="response_note" rows="3" class="input">{{ old('response_note') }}</textarea>
                        @error('response_note') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button class="btn-primary mt-6 w-full">Send answer</button>
                <p class="mt-3 text-center text-xs text-muted">Your answer, name and IP address are recorded with the record. No account needed.</p>
            </form>
        @endif
    </div>
</x-layouts.site>
