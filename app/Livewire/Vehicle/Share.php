<?php

namespace App\Livewire\Vehicle;

use App\Livewire\Concerns\ManagesVehicle;
use App\Models\Vehicle;
use App\Support\Qr;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Share extends Component
{
    use ManagesVehicle;

    #[Locked]
    public Vehicle $vehicle;

    public string $label = '';

    public bool $show_costs = false;

    public bool $show_full_vin = false;

    public bool $show_documents = true;

    public string $expires = '30';

    public ?int $qrFor = null;

    public function create(): void
    {
        $this->validate([
            'label' => ['required', 'string', 'max:80'],
            'expires' => ['required', 'in:7,30,90,never'],
        ], ['label.required' => 'Name the link so you know who has it, e.g. “Insurance quote” or “Buyer — Sam”.']);

        $link = $this->vehicle->shareLinks()->create([
            'created_by' => Auth::id(),
            'label' => $this->label,
            'show_costs' => $this->show_costs,
            'show_full_vin' => $this->show_full_vin,
            'show_documents' => $this->show_documents,
            'expires_at' => $this->expires === 'never' ? null : now()->addDays((int) $this->expires),
        ]);

        $this->reset('label', 'show_costs', 'show_full_vin');
        $this->qrFor = $link->getKey();
        $this->dispatch('toast', message: 'Link created.');
    }

    public function revoke(int $id): void
    {
        $this->vehicle->shareLinks()->whereKey($id)->firstOrFail()->update(['revoked_at' => now()]);
        $this->dispatch('toast', message: 'Link revoked — it stops working immediately.');
    }

    public function render()
    {
        $links = $this->vehicle->shareLinks()->get();
        $qrLink = $links->firstWhere('id', $this->qrFor);

        return view('livewire.vehicle.share', [
            'active' => $links->filter->isActive(),
            'inactive' => $links->reject->isActive()->take(5),
            'qr' => $qrLink?->isActive() ? Qr::svg($qrLink->url(), 200) : null,
        ]);
    }
}
