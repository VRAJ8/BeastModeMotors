<?php

namespace App\Livewire\Vehicle;

use App\Enums\ExpenseCategory;
use App\Livewire\Concerns\ManagesVehicle;
use App\Models\Vehicle;
use App\Services\CostReport;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Running costs for the current owner only — previous owners' spending is never visible.
 */
class Costs extends Component
{
    use ManagesVehicle, WithPagination;

    private const PER_PAGE = 10;

    #[Locked]
    public Vehicle $vehicle;

    public string $category = 'fuel';

    public string $amount = '';

    public string $spent_on = '';

    public ?int $odometer = null;

    public string $volume = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->spent_on = now()->toDateString();
    }

    public function add(): void
    {
        $this->amount = clean_amount($this->amount);
        $this->validate([
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'spent_on' => ['required', 'date', 'before_or_equal:today'],
            'odometer' => ['nullable', 'integer', 'min:0', 'max:2000000'],
            'volume' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:200'],
        ]);

        $this->vehicle->expenses()->create([
            'ownership_id' => $this->vehicle->currentOwnership->getKey(),
            'category' => $this->category,
            'amount_cents' => to_cents($this->amount),
            'spent_on' => $this->spent_on,
            'odometer' => $this->odometer,
            'volume' => $this->volume !== '' ? (float) $this->volume : null,
            'notes' => $this->notes ?: null,
        ]);

        $this->reset('amount', 'odometer', 'volume', 'notes');
        $this->dispatch('toast', message: 'Expense added.');
    }

    public function delete(int $id): void
    {
        $this->vehicle->expenses()
            ->where('ownership_id', $this->vehicle->currentOwnership->getKey())
            ->whereKey($id)
            ->firstOrFail()
            ->delete();

        // Deleting the last expense on the last page would otherwise leave an empty page with no way back.
        $remaining = $this->vehicle->expenses()->where('ownership_id', $this->vehicle->currentOwnership->getKey())->count();
        $lastPage = max(1, (int) ceil($remaining / self::PER_PAGE));

        if ($this->getPage() > $lastPage) {
            $this->setPage($lastPage);
        }
    }

    public function render(CostReport $report)
    {
        $ownership = $this->vehicle->currentOwnership;

        return view('livewire.vehicle.costs', [
            'report' => $report->for($ownership),
            'ownership' => $ownership,
            'expenses' => $this->vehicle->expenses()->where('ownership_id', $ownership->getKey())->paginate(self::PER_PAGE),
            'categories' => ExpenseCategory::options(),
            'unit' => ExpenseCategory::tryFrom($this->category)?->volumeUnit(),
            'labels' => ['maintenance' => 'Maintenance & repairs'] + ExpenseCategory::options(),
        ]);
    }
}
