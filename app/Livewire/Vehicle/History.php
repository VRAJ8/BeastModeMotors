<?php

namespace App\Livewire\Vehicle;

use App\Enums\ServiceCategory;
use App\Enums\VerificationStatus;
use App\Livewire\Concerns\ManagesVehicle;
use App\Models\ServiceRecord;
use App\Models\Vehicle;
use App\Services\ShopVerifier;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class History extends Component
{
    use ManagesVehicle;

    #[Locked]
    public Vehicle $vehicle;

    #[Url(as: 'type', except: '')]
    public string $category = '';

    #[Url(as: 'proof', except: '')]
    public string $evidence = '';

    public ?int $verifyingId = null;

    public string $shopName = '';

    public string $shopEmail = '';

    public function startVerification(int $recordId): void
    {
        $record = $this->findRecord($recordId);
        $this->verifyingId = $record->getKey();
        $this->shopName = (string) $record->provider_name;
        $this->shopEmail = (string) $record->provider_email;
        $this->resetErrorBag();
    }

    public function sendVerification(ShopVerifier $verifier): void
    {
        $this->validate([
            'shopName' => ['required', 'string', 'max:120'],
            'shopEmail' => ['required', 'email', 'max:255'],
        ], [], ['shopName' => 'shop name', 'shopEmail' => 'shop email']);

        $verifier->request($this->findRecord($this->verifyingId), Auth::user(), $this->shopName, $this->shopEmail);

        $this->reset('verifyingId', 'shopName', 'shopEmail');
        $this->dispatch('toast', message: 'Sent. The shop has 14 days to confirm.');
    }

    public function cancelVerification(int $recordId): void
    {
        $this->findRecord($recordId)->verifications()
            ->where('status', VerificationStatus::Pending)
            ->update(['status' => VerificationStatus::Cancelled]);
    }

    public function delete(int $recordId): void
    {
        $this->findRecord($recordId)->delete();
        $this->dispatch('toast', message: 'Record deleted.');
    }

    private function findRecord(?int $id): ServiceRecord
    {
        return $this->vehicle->records()->whereKey($id)->firstOrFail();
    }

    public function render()
    {
        $records = $this->vehicle->records()
            ->withCount('documents')
            ->with(['documents', 'pendingVerification', 'ownership', 'verifications' => fn ($q) => $q->latest()->limit(1)])
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->get()
            ->when($this->evidence, fn ($records) => $records->filter(fn (ServiceRecord $r) => $r->evidence() === $this->evidence));

        $all = $this->vehicle->records()->withCount('documents')->get();

        return view('livewire.vehicle.history', [
            'records' => $records,
            'categories' => ServiceCategory::options(),
            'counts' => $all->countBy(fn (ServiceRecord $r) => $r->evidence()),
            'total' => $all->count(),
            'spend' => $all->sum('cost_cents'),
            'currentOwnershipId' => $this->vehicle->currentOwnership?->getKey(),
        ]);
    }
}
