<?php

namespace App\Livewire;

use App\Livewire\Concerns\GuardsPublicForms;
use App\Models\TestDrive;
use App\Models\Vehicle;
use App\Services\TestDriveScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

class BookTestDrive extends Component
{
    use GuardsPublicForms;

    #[Locked]
    public Vehicle $vehicle;

    public string $date = '';

    public string $time = '';

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:32')]
    public string $phone = '';

    #[Validate('nullable|string|max:1000')]
    public string $notes = '';

    #[Locked]
    public ?string $reference = null;

    public function mount(TestDriveScheduler $scheduler): void
    {
        $this->prefillContactDetails();
        $this->date = $scheduler->bookableDates(1)->first()?->toDateString() ?? '';
    }

    public function updatedDate(): void
    {
        $this->time = '';
        unset($this->timeSlots);
    }

    #[Computed]
    public function dates()
    {
        return app(TestDriveScheduler::class)->bookableDates(14);
    }

    #[Computed]
    public function timeSlots()
    {
        $date = rescue(fn () => CarbonImmutable::createFromFormat('Y-m-d', $this->date)->startOfDay(), report: false);

        return $date ? app(TestDriveScheduler::class)->availableSlots($this->vehicle, $date) : collect();
    }

    public function book(TestDriveScheduler $scheduler): void
    {
        $this->validate([
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
        ]);
        $this->validate();

        if ($this->isSpam()) {
            $this->reference = 'TD-'.strtoupper(str()->random(6));

            return;
        }

        if (! $this->vehicle->isAvailable()) {
            $this->addError('time', 'Sorry — this car is no longer available for test drives.');

            return;
        }

        $at = CarbonImmutable::createFromFormat('Y-m-d H:i', "{$this->date} {$this->time}");

        if (! $scheduler->isSlotAvailable($this->vehicle, $at)) {
            unset($this->timeSlots);
            $this->time = '';
            $this->addError('time', 'That slot was just taken. Please pick another time.');

            return;
        }

        $this->throttle('test-drive');

        $testDrive = TestDrive::create([
            'vehicle_id' => $this->vehicle->id,
            'user_id' => Auth::id(),
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'scheduled_at' => $at,
            'notes' => $this->notes ?: null,
        ]);

        $this->reference = $testDrive->reference;
        $this->dispatch('toast', message: 'Test drive requested — check your inbox.');
    }

    public function render()
    {
        return view('livewire.book-test-drive');
    }
}
