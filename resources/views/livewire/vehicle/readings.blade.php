<form wire:submit="add" class="flex flex-wrap items-end gap-2">
    <div class="min-w-36 flex-1">
        <label class="label" for="reading">Odometer now</label>
        <div class="relative">
            <input id="reading" type="number" wire:model="reading" class="input num pr-9" placeholder="{{ $vehicle->current_mileage }}">
            <span class="absolute inset-y-0 right-3 grid place-items-center text-xs text-muted">mi</span>
        </div>
    </div>
    <div>
        <label class="label" for="recorded_on">Date</label>
        <input id="recorded_on" type="date" wire:model="recorded_on" max="{{ now()->toDateString() }}" class="input">
    </div>
    <button class="btn-primary">Update</button>
    @error('reading') <p class="error w-full">{{ $message }}</p> @enderror
    @error('recorded_on') <p class="error w-full">{{ $message }}</p> @enderror
    @if ($entries->isNotEmpty())
        <p class="w-full text-xs text-muted">
            Your entries:
            @foreach ($entries as $entry)
                <span class="num">{{ miles($entry->reading) }}</span> on {{ $entry->recorded_on->format('M j, Y') }}
                <button type="button" wire:click="remove({{ $entry->id }})" wire:confirm="Remove this reading?" class="font-semibold underline hover:text-danger">Remove</button>@if (! $loop->last) · @endif
            @endforeach
        </p>
    @endif
</form>
