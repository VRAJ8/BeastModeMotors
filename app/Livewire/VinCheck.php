<?php

namespace App\Livewire;

use App\Models\Listing;
use App\Services\Nhtsa;
use App\Services\VehicleLookup;
use App\Services\VinDecoder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Free public tool: check-digit validation, decoding and open recalls for any VIN.
 */
class VinCheck extends Component
{
    #[Url(except: '')]
    public string $vin = '';

    /** @var array<string, mixed>|null */
    public ?array $result = null;

    /** @var list<array<string, mixed>>|null */
    public ?array $recalls = null;

    public ?string $listingUrl = null;

    public function mount(): void
    {
        if ($this->vin !== '') {
            $this->check(app(VehicleLookup::class), app(Nhtsa::class));
        }
    }

    public function check(VehicleLookup $lookup, Nhtsa $nhtsa): void
    {
        $this->vin = VinDecoder::normalize($this->vin);
        $this->reset('result', 'recalls', 'listingUrl');

        $this->validate(['vin' => ['required', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/']], [
            'vin.size' => 'A VIN is exactly 17 characters — that one has '.strlen($this->vin).'.',
            'vin.regex' => 'VINs never contain I, O or Q.',
        ]);

        $key = 'vin-check:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            $this->addError('vin', 'Too many checks — try again in a few minutes.');

            return;
        }
        RateLimiter::hit($key, 600);

        $this->result = $lookup->lookup($this->vin);
        $details = $this->result['details'];

        if (filled($details['make'] ?? null) && filled($details['model'] ?? null) && filled($details['year'] ?? null)) {
            $this->recalls = $nhtsa->recalls($details['make'], $details['model'], (int) $details['year']);
        }

        $listing = Listing::public()->whereHas('vehicle', fn ($q) => $q->where('vin', $this->vin))->first();
        $this->listingUrl = $listing ? route('listings.show', $listing) : null;
    }

    public function render()
    {
        return view('livewire.vin-check');
    }
}
