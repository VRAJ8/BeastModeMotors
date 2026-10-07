<?php

namespace App\Livewire\Vehicle;

use App\Livewire\Concerns\ManagesVehicle;
use App\Models\Vehicle;
use App\Services\RecallSync;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Recalls extends Component
{
    use ManagesVehicle;

    #[Locked]
    public Vehicle $vehicle;

    /** @var array<int, string> recall id => service record id */
    public array $fixRecord = [];

    public function check(RecallSync $sync): void
    {
        $new = $sync->sync($this->vehicle, notify: false);

        $this->dispatch('toast', message: match (true) {
            $new === null => 'NHTSA couldn\'t be reached right now. We\'ll keep checking weekly.',
            $new->isEmpty() => 'Checked — no new recalls.',
            default => $new->count().' new recall(s) found.',
        });
    }

    public function resolve(int $id): void
    {
        $recordId = $this->fixRecord[$id] ?? null;
        $record = $recordId ? $this->vehicle->records()->whereKey($recordId)->first() : null;

        $this->vehicle->recalls()->whereKey($id)->firstOrFail()->update([
            'resolved_at' => $record?->performed_on ?? now(),
            'service_record_id' => $record?->getKey(),
        ]);
    }

    public function reopen(int $id): void
    {
        $this->vehicle->recalls()->whereKey($id)->firstOrFail()->update(['resolved_at' => null, 'service_record_id' => null]);
    }

    public function render()
    {
        $recalls = $this->vehicle->recalls()->with('record')->get();

        return view('livewire.vehicle.recalls', [
            'open' => $recalls->filter->isOpen(),
            'resolved' => $recalls->reject->isOpen(),
            'records' => $this->vehicle->records()->get(['id', 'title', 'performed_on']),
            'checkedAt' => $this->vehicle->fresh()->recalls_checked_at,
        ]);
    }
}
