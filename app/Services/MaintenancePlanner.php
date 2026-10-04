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
     * Mark the chosen reminders as done by this record (only if it's newer than what's on file).
     *
     * @param  list<int>  $reminderIds
     */
    public function applyRecord(ServiceRecord $record, array $reminderIds): void
    {
        $reminders = $record->vehicle->reminders()->whereKey($reminderIds)->get();

        $reminders->each(function (Reminder $reminder) use ($record) {
            if ($reminder->last_done_on === null || $record->performed_on->gte($reminder->last_done_on)) {
                $reminder->update([
                    'last_done_on' => $record->performed_on,
                    'last_done_mileage' => $record->mileage,
                    'notified_at' => null,
                ]);
            }
        });

        $record->update(['tasks' => $reminders->pluck('task')->values()->all() ?: null]);
    }
}
