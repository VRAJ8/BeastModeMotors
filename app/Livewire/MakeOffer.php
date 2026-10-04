<?php

namespace App\Livewire;

use App\Enums\LeadType;
use App\Livewire\Concerns\GuardsPublicForms;
use App\Models\Lead;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class MakeOffer extends Component
{
    use GuardsPublicForms;

    #[Locked]
    public Vehicle $vehicle;

    public ?int $amount = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $message = '';

    public bool $sent = false;

    public function mount(): void
    {
        $this->prefillContactDetails();
        $this->amount = (int) (round($this->vehicle->price * 0.95 / 1000) * 1000);
    }

    protected function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:'.(int) ceil($this->vehicle->price * 0.5), 'max:'.$this->vehicle->price],
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:32',
            'message' => 'nullable|string|max:1000',
        ];
    }

    protected function messages(): array
    {
        return [
            'amount.min' => 'Offers below '.money(ceil($this->vehicle->price * 0.5)).' can\'t be considered for this car.',
            'amount.max' => 'Good news — you don\'t need to offer more than the asking price.',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        if (! $this->isSpam()) {
            $this->throttle('offer');

            Lead::create([
                'type' => LeadType::Offer,
                'vehicle_id' => $this->vehicle->id,
                'user_id' => Auth::id(),
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone ?: null,
                'offer_amount' => $this->amount,
                'message' => $this->message ?: null,
            ]);
        }

        $this->sent = true;
        $this->dispatch('toast', message: 'Offer sent. A specialist will be in touch within one business day.');
    }

    public function render()
    {
        return view('livewire.make-offer');
    }
}
