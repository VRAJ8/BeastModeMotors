<?php

namespace App\Livewire;

use App\Models\Vehicle;
use App\Support\CompareList;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Save (favourite) and compare toggles for a vehicle.
 */
class VehicleActions extends Component
{
    #[Locked]
    public Vehicle $vehicle;

    public bool $compact = false;

    public function toggleFavorite()
    {
        if (! Auth::check()) {
            session()->put('url.intended', route('vehicles.show', $this->vehicle));

            return $this->redirectRoute('login');
        }

        $result = Auth::user()->favorites()->toggle($this->vehicle->id);

        $this->dispatch('toast', message: $result['attached']
            ? 'Saved to your garage. We\'ll email you if the price drops.'
            : 'Removed from your garage.');
    }

    public function toggleCompare(CompareList $compare): void
    {
        if (! $compare->toggle($this->vehicle)) {
            $this->dispatch('toast', message: 'You can compare up to '.CompareList::MAX.' cars at once.', type: 'error');

            return;
        }

        $this->dispatch('compare-updated');
        $this->dispatch('toast', message: $compare->has($this->vehicle) ? 'Added to compare.' : 'Removed from compare.');
    }

    public function render(CompareList $compare)
    {
        return view('livewire.vehicle-actions', [
            'isFavorite' => Auth::user()?->hasFavorited($this->vehicle) ?? false,
            'inCompare' => $compare->has($this->vehicle),
        ]);
    }
}
