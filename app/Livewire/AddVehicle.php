<?php

namespace App\Livewire;

use App\Enums\AcquiredVia;
use App\Enums\FuelType;
use App\Models\Vehicle;
use App\Notifications\OwnershipReviewRequested;
use App\Services\Garage;
use App\Services\RecallSync;
use App\Services\VehicleLookup;
use App\Services\VinDecoder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AddVehicle extends Component
{
    #[Locked]
    public int $step = 1;

    public string $vin = '';

    /** @var array<string, mixed>|null */
    #[Locked]
    public ?array $lookup = null;

    public ?int $year = null;

    public string $make = '';

    public string $model = '';

    public string $trim = '';

    public string $body = '';

    public string $engine = '';

    public string $drivetrain = '';

    public string $transmission = '';

    public string $fuel_type = 'gasoline';

    public string $exterior_color = '';

    public string $nickname = '';

    public string $acquired_via = 'dealer';

    public string $started_on = '';

    public ?int $start_mileage = null;

    public ?int $current_mileage = null;

    public string $purchase_price = '';

    /** The VIN another account already holds a passport for, which this user can ask staff to review. */
    #[Locked]
    public ?string $contested = null;

    #[Locked]
    public bool $reviewRequested = false;

    public function mount(): void
    {
        $this->started_on = now()->toDateString();
    }

    public function decode(VehicleLookup $lookup): void
    {
        $this->vin = VinDecoder::normalize($this->vin);

        $this->validate([
            'vin' => ['required', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/'],
        ], [
            'vin.size' => 'A VIN is exactly 17 characters — you have '.strlen($this->vin).'.',
            'vin.regex' => 'VINs never contain the letters I, O or Q (they look like 1 and 0).',
        ]);

        $this->contested = null;
        $this->reviewRequested = false;

        if ($existing = Vehicle::where('vin', $this->vin)->first()) {
            $mine = $existing->user_id === Auth::id();
            $this->contested = $mine ? null : $this->vin;
            $this->addError('vin', match (true) {
                $mine => 'This car is already in your garage.',
                $existing->user_id === null => 'This car already has a passport, and it\'s waiting for its next owner. If that\'s you, request a review below and we\'ll check your paperwork and hand it over, history included.',
                default => 'This car already has a passport with another owner. If you bought it privately, ask the seller to transfer it to you through a deal so its history comes with it.',
            });

            return;
        }

        $this->lookup = $lookup->lookup($this->vin);
        $details = $this->lookup['details'];

        $this->year = $details['year'] ?? null;
        $this->make = $details['make'] ?? '';
        $this->model = $details['model'] ?? '';
        $this->trim = (string) ($details['trim'] ?? '');
        $this->body = (string) ($details['body'] ?? '');
        $this->engine = (string) ($details['engine'] ?? '');
        $this->drivetrain = (string) ($details['drivetrain'] ?? '');
        $this->transmission = (string) ($details['transmission'] ?? '');
        $this->fuel_type = $details['fuel_type'] ?? 'gasoline';

        $this->step = 2;
    }

    /**
     * Someone else registered this VIN. Anyone can type a VIN, so let the real owner ask a person to look.
     */
    public function requestReview(): void
    {
        $vehicle = $this->contested ? Vehicle::where('vin', $this->contested)->first() : null;

        if (! $vehicle || $vehicle->user_id === Auth::id() || $this->reviewRequested) {
            return;
        }

        if (RateLimiter::tooManyAttempts('ownership-review:'.Auth::id(), 3)) {
            $this->addError('vin', 'You\'ve sent several review requests today. Our team will reply to those first.');

            return;
        }

        RateLimiter::hit('ownership-review:'.Auth::id(), 86400);

        Notification::route('mail', config('passport.support_email'))->notify(new OwnershipReviewRequested($vehicle, Auth::user()));

        $this->reviewRequested = true;
    }

    public function confirmDetails(): void
    {
        $this->validate($this->detailRules());
        $this->step = 3;
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function save(Garage $garage, RecallSync $recalls)
    {
        $this->purchase_price = clean_amount($this->purchase_price);
        // The VIN field is client-editable after decoding: never trust it at save time.
        if ($this->lookup === null || VinDecoder::normalize($this->vin) !== $this->lookup['vin']) {
            $this->step = 1;
            $this->addError('vin', 'The VIN changed — decode it again.');

            return null;
        }

        if (Vehicle::where('vin', $this->lookup['vin'])->exists()) {
            $this->step = 1;
            $this->addError('vin', 'This car already has a passport.');

            return null;
        }

        $this->validate($this->detailRules());
        $this->validate([
            'acquired_via' => ['required', Rule::enum(AcquiredVia::class)->except([AcquiredVia::Platform])],
            'started_on' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.($this->year - 1).'-01-01'],
            'start_mileage' => ['required', 'integer', 'min:0', 'max:2000000'],
            'current_mileage' => ['required', 'integer', 'gte:start_mileage', 'max:2000000'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
        ], [
            'started_on.after_or_equal' => 'That\'s before the car was built.',
            'current_mileage.gte' => 'The current reading can\'t be lower than when you bought it.',
        ]);

        $vehicle = $garage->register(Auth::user(), [
            'vin' => $this->lookup['vin'],
            'year' => $this->year,
            'make' => trim($this->make),
            'model' => trim($this->model),
            'trim' => $this->trim ?: null,
            'body' => $this->body ?: null,
            'engine' => $this->engine ?: null,
            'drivetrain' => $this->drivetrain ?: null,
            'transmission' => $this->transmission ?: null,
            'fuel_type' => $this->fuel_type,
            'exterior_color' => $this->exterior_color ?: null,
            'nickname' => $this->nickname ?: null,
            'decoded' => $this->lookup['details'] ?? null,
            'decode_source' => $this->lookup['source'] ?? null,
        ], [
            'acquired_via' => $this->acquired_via,
            'started_on' => $this->started_on,
            'start_mileage' => (int) $this->start_mileage,
            'current_mileage' => (int) $this->current_mileage,
            'purchase_price_cents' => to_cents($this->purchase_price),
        ]);

        $recalls->sync($vehicle, notify: false);

        session()->flash('toast', 'Passport created. Log your most recent service next.');

        return $this->redirectRoute('vehicles.show', $vehicle, navigate: false);
    }

    /**
     * @return array<string, mixed>
     */
    private function detailRules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
            'make' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:80'],
            'trim' => ['nullable', 'string', 'max:120'],
            'fuel_type' => ['required', Rule::enum(FuelType::class)],
            'exterior_color' => ['nullable', 'string', 'max:40'],
            'nickname' => ['nullable', 'string', 'max:60'],
        ];
    }

    public function render()
    {
        return view('livewire.add-vehicle', [
            'fuels' => FuelType::options(),
            'acquisitions' => collect(AcquiredVia::options())->except(AcquiredVia::Platform->value)->all(),
        ]);
    }
}
