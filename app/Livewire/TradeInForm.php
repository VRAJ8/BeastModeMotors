<?php

namespace App\Livewire;

use App\Enums\LeadType;
use App\Livewire\Concerns\GuardsPublicForms;
use App\Models\Lead;
use App\Services\TradeInEstimator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TradeInForm extends Component
{
    use GuardsPublicForms;

    public int $step = 1;

    public string $make = '';

    public string $model = '';

    public ?int $year = null;

    public ?int $mileage = null;

    public ?int $originalPrice = null;

    public string $vehicleCondition = 'good';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $message = '';

    public bool $sent = false;

    public function mount(): void
    {
        $this->prefillContactDetails();
    }

    protected function vehicleRules(): array
    {
        return [
            'make' => 'required|string|max:60',
            'model' => 'required|string|max:80',
            'year' => 'required|integer|min:1960|max:'.((int) date('Y') + 1),
            'mileage' => 'required|integer|min:0|max:500000',
            'originalPrice' => 'required|integer|min:10000|max:10000000',
            'vehicleCondition' => 'required|in:'.implode(',', array_keys(TradeInEstimator::CONDITION_FACTORS)),
        ];
    }

    protected function validationAttributes(): array
    {
        return ['originalPrice' => 'original price', 'vehicleCondition' => 'condition'];
    }

    public function estimate(): void
    {
        $this->validate($this->vehicleRules());
        $this->step = 2;
    }

    #[Computed]
    public function valuation(): ?array
    {
        if ($this->step < 2) {
            return null;
        }

        return app(TradeInEstimator::class)->estimate($this->originalPrice, $this->year, $this->mileage, $this->vehicleCondition);
    }

    public function submit(): void
    {
        $this->validate($this->vehicleRules() + [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:32',
            'message' => 'nullable|string|max:1000',
        ]);

        if (! $this->isSpam()) {
            $this->throttle('trade-in');

            Lead::create([
                'type' => LeadType::TradeIn,
                'user_id' => Auth::id(),
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone ?: null,
                'message' => $this->message ?: null,
                'meta' => [
                    'make' => $this->make,
                    'model' => $this->model,
                    'year' => $this->year,
                    'mileage' => $this->mileage,
                    'original_price' => $this->originalPrice,
                    'condition' => $this->vehicleCondition,
                    'estimate' => $this->valuation,
                ],
            ]);
        }

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.trade-in-form');
    }
}
