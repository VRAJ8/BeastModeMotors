<?php

namespace App\Livewire\Vehicle;

use App\Enums\DealStatus;
use App\Enums\FuelType;
use App\Livewire\Concerns\ManagesVehicle;
use App\Models\Deal;
use App\Models\Vehicle;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Settings extends Component
{
    use ManagesVehicle;

    #[Locked]
    public Vehicle $vehicle;

    public string $nickname = '';

    public string $trim = '';

    public string $exterior_color = '';

    public string $engine = '';

    public string $transmission = '';

    public string $drivetrain = '';

    public string $fuel_type = '';

    public string $confirmVin = '';

    public function mount(): void
    {
        foreach (['nickname', 'trim', 'exterior_color', 'engine', 'transmission', 'drivetrain'] as $field) {
            $this->{$field} = (string) $this->vehicle->{$field};
        }

        $this->fuel_type = $this->vehicle->fuel_type->value;
    }

    public function save(): void
    {
        $data = $this->validate([
            'nickname' => ['nullable', 'string', 'max:60'],
            'trim' => ['nullable', 'string', 'max:120'],
            'exterior_color' => ['nullable', 'string', 'max:40'],
            'engine' => ['nullable', 'string', 'max:120'],
            'transmission' => ['nullable', 'string', 'max:60'],
            'drivetrain' => ['nullable', 'string', 'max:40'],
            'fuel_type' => ['required', Rule::enum(FuelType::class)],
        ]);

        $this->vehicle->update(array_map(fn ($v) => $v === '' ? null : $v, $data));
        $this->dispatch('toast', message: 'Details saved.');
    }

    public function delete()
    {
        $this->confirmVin = strtoupper(trim($this->confirmVin));
        $this->validate(['confirmVin' => ['required', Rule::in([substr($this->vehicle->vin, -6)])]], [
            'confirmVin.in' => 'Type the last six characters of the VIN to confirm.',
        ]);

        $busy = Deal::where('vehicle_id', $this->vehicle->getKey())->whereIn('status', [DealStatus::Open, DealStatus::Agreed])->exists();

        if ($busy) {
            $this->addError('confirmVin', 'Close the open deals on this car first.');

            return null;
        }

        // History from earlier owners isn't yours to erase (and deleting it would let the VIN start over clean).
        if ($this->vehicle->ownerships()->count() > 1 || Deal::where('vehicle_id', $this->vehicle->getKey())->where('status', DealStatus::Completed)->exists()) {
            $this->addError('confirmVin', 'This passport carries history from previous owners, so it can\'t be deleted. If you no longer have the car, sell it through a deal so the history goes to the next owner.');

            return null;
        }

        // A shop's "that wasn't us" is part of the car's story; deleting and re-adding the VIN must not erase it.
        if ($this->vehicle->records()->whereNotNull('disputed_at')->exists()) {
            $this->addError('confirmVin', 'A shop has disputed a record on this passport, and disputes stay with the car, so it can\'t be deleted. If you no longer have the car, sell it through a deal, or contact support.');

            return null;
        }

        $this->vehicle->delete();
        session()->flash('toast', 'Passport deleted.');

        return $this->redirectRoute('garage');
    }

    public function render()
    {
        return view('livewire.vehicle.settings', ['fuels' => FuelType::options()]);
    }
}
