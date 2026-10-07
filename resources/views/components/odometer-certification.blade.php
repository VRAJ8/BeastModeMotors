@props(['model', 'rollbacks' => []])
{{-- The seller's federal odometer certification for a handover reading (see App\Enums\OdometerStatus). --}}
<fieldset>
    <legend class="label">Odometer certification</legend>
    <p class="text-xs text-muted">Federal law makes you certify this reading on the odometer disclosure. Pick what's true.</p>
    @if ($rollbacks)
        <p class="mt-2 rounded-xl border border-warn/30 bg-warn-soft p-3 text-xs text-ink">This car's passport shows the odometer going backwards ({{ miles($rollbacks[0]['reading']) }} on {{ \Illuminate\Support\Carbon::parse($rollbacks[0]['date'])->format('M j, Y') }}, after {{ miles($rollbacks[0]['previous_max']) }}). If that was a typo, fix the record. If the odometer was replaced or reset, the reading isn't the actual mileage.</p>
    @endif
    <div class="mt-2 space-y-2">
        @foreach (\App\Enums\OdometerStatus::cases() as $option)
            <label class="flex items-start gap-2 rounded-xl border border-line p-3 text-sm has-[:checked]:border-ink" wire:key="os-{{ $option->value }}">
                <input type="radio" wire:model="{{ $model }}" value="{{ $option->value }}" class="mt-0.5">
                <span><span class="font-medium">{{ $option->label() }}</span><span class="block text-xs text-muted">{{ $option->explanation() }}</span></span>
            </label>
        @endforeach
    </div>
    @error('odometer_status') <p class="error">{{ $message }}</p> @enderror
</fieldset>
