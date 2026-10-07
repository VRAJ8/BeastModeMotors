<?php

namespace App\Livewire\Vehicle;

use App\Livewire\Concerns\ManagesVehicle;
use App\Models\Reminder;
use App\Models\ServiceRecord;
use App\Models\Vehicle;
use App\Services\MaintenancePlanner;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Maintenance extends Component
{
    use ManagesVehicle;

    #[Locked]
    public Vehicle $vehicle;

    /** Editing state: null = closed, 0 = new, id = existing. */
    public ?int $editingId = null;

    public string $task = '';

    public ?int $interval_miles = null;

    public ?int $interval_months = null;

    public string $last_done_on = '';

    public ?int $last_done_mileage = null;

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = 0;
    }

    public function edit(int $id): void
    {
        $reminder = $this->find($id);
        $this->resetErrorBag();
        $this->editingId = $id;
        $this->task = $reminder->task;
        $this->interval_miles = $reminder->interval_miles;
        $this->interval_months = $reminder->interval_months;
        $this->last_done_on = (string) $reminder->last_done_on?->toDateString();
        $this->last_done_mileage = $reminder->last_done_mileage;
    }

    public function save(MaintenancePlanner $planner): void
    {
        $data = $this->validate([
            'task' => ['required', 'string', 'max:80'],
            'interval_miles' => ['nullable', 'integer', 'min:100', 'max:500000', 'required_without:interval_months'],
            'interval_months' => ['nullable', 'integer', 'min:1', 'max:240', 'required_without:interval_miles'],
            'last_done_on' => ['nullable', 'date', 'before_or_equal:today'],
            'last_done_mileage' => ['nullable', 'integer', 'min:0', 'max:'.max(1, $this->vehicle->current_mileage)],
        ], [
            'interval_miles.required_without' => 'Set a mileage or a time interval (or both).',
            'last_done_mileage.max' => 'That\'s more than the car\'s current odometer.',
        ]);

        $done = ['on' => $data['last_done_on'] ?: null, 'mileage' => $data['last_done_mileage']];
        unset($data['last_done_on'], $data['last_done_mileage']);

        $reminder = $this->editingId ? $this->find($this->editingId) : $this->vehicle->reminders()->make();

        // Records that ticked this task point at it by name, so a rename takes them along.
        if ($reminder->exists && $reminder->task !== $data['task']) {
            $this->vehicle->records()->whereNotNull('tasks')->get()
                ->filter(fn (ServiceRecord $r) => in_array($reminder->task, $r->tasks, true))
                ->each(fn (ServiceRecord $r) => $r->update(['tasks' => array_values(array_unique(array_map(fn ($t) => $t === $reminder->task ? $data['task'] : $t, $r->tasks)))]));
        }

        $reminder->fill($data);

        // Only what the owner changed by hand becomes their own entry; dates that came from a record stay linked to it.
        if (! $reminder->exists || $done['on'] !== $reminder->last_done_on?->toDateString() || $done['mileage'] !== $reminder->last_done_mileage) {
            $reminder->fill(['baseline_done_on' => $done['on'], 'baseline_done_mileage' => $done['mileage']]);
        }

        $reminder->save();
        $planner->refresh($reminder);

        $this->editingId = null;
        $this->dispatch('toast', message: 'Maintenance plan updated.');
    }

    public function delete(int $id): void
    {
        $this->find($id)->delete();
        $this->editingId = null;
    }

    private function resetForm(): void
    {
        $this->reset('task', 'interval_miles', 'interval_months', 'last_done_on', 'last_done_mileage');
        $this->resetErrorBag();
    }

    private function find(int $id): Reminder
    {
        return $this->vehicle->reminders()->whereKey($id)->firstOrFail();
    }

    public function render()
    {
        $mileage = $this->vehicle->fresh()->current_mileage;
        $order = [Reminder::OVERDUE => 0, Reminder::DUE_SOON => 1, Reminder::UNKNOWN => 2, Reminder::OK => 3];

        return view('livewire.vehicle.maintenance', [
            'mileage' => $mileage,
            'reminders' => $this->vehicle->reminders()->get()
                ->sortBy(fn (Reminder $r) => [$order[$r->status($mileage)], -$r->progress($mileage)])
                ->values(),
        ]);
    }
}
