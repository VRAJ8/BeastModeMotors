<?php

namespace App\Services;

use App\Enums\FuelType;
use App\Models\Reminder;
use App\Models\ServiceRecord;
use App\Models\Vehicle;

class MaintenancePlanner
{
    public function createDefaults(Vehicle $vehicle, FuelType $fuel): void
    {
        foreach (config("passport.maintenance.{$fuel->schedule()}") as [$task, $miles, $months]) {
            $vehicle->reminders()->create([
                'task' => $task,
                'interval_miles' => $miles,
                'interval_months' => $months,
            ]);
        }
    }

    /**
     * Mark the chosen reminders as done by this record, and roll back any it no longer covers.
     *
     * @param  list<int>  $reminderIds
     */
    public function applyRecord(ServiceRecord $record, array $reminderIds): void
    {
        $chosen = $record->vehicle->reminders()->whereKey($reminderIds)->get();

        $record->update(['tasks' => $chosen->pluck('task')->values()->all() ?: null]);

        $record->vehicle->reminders()
            ->where(fn ($q) => $q->whereKey($chosen->modelKeys())->orWhere('last_done_record_id', $record->getKey()))
            ->get()
            ->each(fn (Reminder $reminder) => $this->refresh($reminder));
    }

    /**
     * A record is about to be deleted: whatever it completed falls back to the next-latest record, or to what
     * the owner entered by hand.
     */
    public function forget(ServiceRecord $record): void
    {
        Reminder::where('last_done_record_id', $record->getKey())->get()
            ->each(fn (Reminder $reminder) => $this->refresh($reminder, except: $record->getKey()));
    }

    /**
     * When the reminder was last done: the latest record that covers it, or the owner's own entry if that's newer.
     */
    public function refresh(Reminder $reminder, ?int $except = null): void
    {
        $record = ServiceRecord::where('vehicle_id', $reminder->vehicle_id)
            ->whereNotNull('tasks')
            ->when($except, fn ($q) => $q->whereKeyNot($except))
            ->orderByDesc('performed_on')->orderByDesc('mileage')->orderByDesc('id')
            ->get()
            ->first(fn (ServiceRecord $r) => in_array($reminder->task, $r->tasks ?? [], true));

        $baselineNewer = match (true) {
            $record === null => true,
            $reminder->baseline_done_on !== null => $reminder->baseline_done_on->gt($record->performed_on),
            $reminder->baseline_done_mileage !== null => $reminder->baseline_done_mileage > $record->mileage,
            default => false,
        };

        $values = $baselineNewer
            ? ['last_done_on' => $reminder->baseline_done_on, 'last_done_mileage' => $reminder->baseline_done_mileage, 'last_done_record_id' => null]
            : ['last_done_on' => $record->performed_on, 'last_done_mileage' => $record->mileage, 'last_done_record_id' => $record->getKey()];

        $reminder->fill($values);

        if ($reminder->isDirty(['last_done_on', 'last_done_mileage'])) {
            $reminder->notified_at = null;
        }

        $reminder->save();
    }
}
