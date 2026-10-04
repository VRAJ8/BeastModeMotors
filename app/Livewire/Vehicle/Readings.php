<?php

namespace App\Livewire\Vehicle;

use App\Enums\OdometerSource;
use App\Livewire\Concerns\ManagesVehicle;
use App\Models\Vehicle;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Quick "add an odometer reading" on the overview.
 */
class Readings extends Component
{
    use ManagesVehicle;

    #[Locked]
    public Vehicle $vehicle;

    public ?int $reading = null;

    public string $recorded_on = '';

    public function mount(): void
    {
        $this->recorded_on = now()->toDateString();
    }

    public function add()
    {
        $this->validate([
            'reading' => ['required', 'integer', 'min:'.$this->vehicle->current_mileage, 'max:2000000'],
            'recorded_on' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'reading.min' => 'Lower than the last reading ('.number_format($this->vehicle->current_mileage).' mi). If that was a typo, correct the record it came from.',
        ]);

        $this->vehicle->readings()->create([
            'ownership_id' => $this->vehicle->currentOwnership?->getKey(),
            'reading' => $this->reading,
            'recorded_on' => $this->recorded_on,
            'source' => OdometerSource::Manual,
        ]);
        $this->vehicle->refreshMileage();

        session()->flash('toast', 'Odometer updated.');

        return $this->redirectRoute('vehicles.show', $this->vehicle);
    }

    public function render()
    {
        return view('livewire.vehicle.readings');
    }
}
