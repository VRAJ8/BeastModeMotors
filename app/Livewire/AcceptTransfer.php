<?php

namespace App\Livewire;

use App\Enums\AcquiredVia;
use App\Models\PassportTransfer;
use App\Services\PassportTransfers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The new owner's side of an off-marketplace sale: check the car's VIN and take the passport.
 */
class AcceptTransfer extends Component
{
    /** Ways the new owner can say they got the car. */
    public const VIA = [AcquiredVia::PrivateSale, AcquiredVia::Dealer, AcquiredVia::Other];

    #[Locked]
    public PassportTransfer $transfer;

    public string $vinTail = '';

    public string $acquiredVia = 'private_sale';

    public string $price = '';

    public function accept(PassportTransfers $transfers)
    {
        $this->validate([
            'vinTail' => ['required', 'string', 'size:'.PassportTransfers::VIN_TAIL],
            'acquiredVia' => ['required', 'in:'.collect(self::VIA)->map->value->implode(',')],
            'price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
        ], [], ['vinTail' => 'VIN ending', 'price' => 'price']);

        // Six characters are easy to guess at, so a link only gets a few tries.
        $key = 'transfer-vin:'.$this->transfer->getKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('vin_tail', 'Too many tries. Ask the owner for a fresh link.');

            return null;
        }

        RateLimiter::hit($key, 3600);

        $vehicle = $transfers->accept($this->transfer, Auth::user(), $this->vinTail, AcquiredVia::from($this->acquiredVia), to_cents($this->price));

        RateLimiter::clear($key);
        session()->flash('toast', 'The '.$vehicle->title().' and its history are now in your garage.');

        return $this->redirectRoute('vehicles.show', $vehicle);
    }

    public function decline(PassportTransfers $transfers): void
    {
        $transfers->decline($this->transfer, Auth::user());
        $this->transfer->refresh();
    }

    public function render(PassportTransfers $transfers)
    {
        $this->transfer->loadMissing('vehicle', 'sender');

        return view('livewire.accept-transfer', [
            'problem' => $transfers->problem($this->transfer, Auth::user()),
            'via' => self::VIA,
        ]);
    }
}
