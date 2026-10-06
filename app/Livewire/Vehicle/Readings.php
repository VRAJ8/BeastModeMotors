<?php

namespace App\Livewire\Vehicle;

use App\Enums\OdometerSource;
use App\Livewire\Concerns\ManagesVehicle;
use App\Models\OdometerReading;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
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
            'reading' => ['required', 'integer', 'min:0', 'max:2000000'],
            'recorded_on' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$this->vehicle->year.'-01-01'],
        ], [
            'recorded_on.after_or_equal' => 'That\'s before the car was built.',
        ]);

        // Readings must fit the timeline on both sides, or the car gets a rollback flag it doesn't deserve.
        $before = $this->vehicle->readings()->reorder()->whereDate('recorded_on', '<=', $this->recorded_on)->max('reading');
        $after = $this->vehicle->readings()->reorder()->whereDate('recorded_on', '>', $this->recorded_on)->min('reading');

        if ($before !== null && $this->reading < $before) {
            throw ValidationException::withMessages(['reading' => 'Lower than a reading from on or before that date ('.number_format($before).' mi). If that was a typo, correct the entry it came from.']);
        }

        if ($after !== null && $this->reading > $after) {
            throw ValidationException::withMessages(['reading' => 'Higher than a later reading ('.number_format($after).' mi). Check the date.']);
        }

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

    /**
     * Owners can take back their own typed-in readings; readings from records, fuel logs and sales stay.
     */
    public function remove(int $id)
    {
        $this->ownEntries()->whereKey($id)->firstOrFail()->delete();
        $this->vehicle->refreshMileage();

        session()->flash('toast', 'Reading removed.');

        return $this->redirectRoute('vehicles.show', $this->vehicle);
    }

    /**
     * @return HasMany<OdometerReading, Vehicle>
     */
    private function ownEntries(): HasMany
    {
        return $this->vehicle->readings()
            ->where('source', OdometerSource::Manual)
            ->where('ownership_id', $this->vehicle->currentOwnership?->getKey());
    }

    public function render()
    {
        return view('livewire.vehicle.readings', [
            'entries' => $this->ownEntries()->reorder()->latest('recorded_on')->latest('id')->take(3)->get(),
        ]);
    }
}
