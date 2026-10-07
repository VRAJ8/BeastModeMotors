<?php

namespace App\Livewire\Vehicle;

use App\Enums\DealStatus;
use App\Enums\OdometerStatus;
use App\Livewire\Concerns\ManagesVehicle;
use App\Models\Deal;
use App\Models\PassportTransfer;
use App\Models\Vehicle;
use App\Services\OdometerAnalyzer;
use App\Services\PassportTransfers;
use App\Support\OdometerDisclosure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The owner's side of an off-marketplace sale: make a one-time link that moves the passport to the new owner.
 */
class Transfer extends Component
{
    use ManagesVehicle;

    #[Locked]
    public Vehicle $vehicle;

    public bool $starting = false;

    public ?int $saleMileage = null;

    public string $odometerStatus = 'actual';

    /** The link just made. Only its hash is stored, so this is the one time the owner sees it. */
    #[Locked]
    public ?string $link = null;

    public function start(): void
    {
        $this->saleMileage = $this->vehicle->current_mileage;
        $this->starting = true;
    }

    public function create(PassportTransfers $transfers): void
    {
        $status = OdometerStatus::tryFrom($this->odometerStatus)
            ?? throw ValidationException::withMessages(['odometer_status' => 'Choose how you certify the odometer reading.']);

        [, $token] = $transfers->create($this->vehicle, Auth::user(), $this->saleMileage, $status);

        $this->link = route('transfers.show', $token);
        $this->starting = false;
    }

    public function cancel(PassportTransfers $transfers): void
    {
        if ($pending = $this->pending()) {
            $transfers->cancel($pending, Auth::user());
        }

        $this->link = null;
        $this->dispatch('toast', message: 'Transfer link cancelled.');
    }

    public function render(OdometerAnalyzer $odometer)
    {
        $pending = $this->pending();

        return view('livewire.vehicle.transfer', [
            'pending' => $pending,
            'agreed' => Deal::where('vehicle_id', $this->vehicle->getKey())->where('status', DealStatus::Agreed)->exists(),
            'disclosure' => OdometerDisclosure::required($this->vehicle),
            'rollbacks' => $this->starting ? $odometer->anomalies($this->vehicle->readings()->get()) : [],
        ]);
    }

    private function pending(): ?PassportTransfer
    {
        return $this->vehicle->transfers()->open()->where('expires_at', '>', now())->latest('id')->first();
    }
}
