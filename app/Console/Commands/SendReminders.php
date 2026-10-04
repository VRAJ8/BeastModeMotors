<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\Reminder;
use App\Models\Vehicle;
use App\Notifications\DocumentExpiring;
use App\Notifications\MaintenanceDue;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'passport:send-reminders';

    protected $description = 'Email owners about maintenance coming due and documents about to expire';

    public function handle(): int
    {
        $maintenance = 0;

        Vehicle::with(['owner', 'reminders'])->chunkById(100, function ($vehicles) use (&$maintenance) {
            foreach ($vehicles as $vehicle) {
                // One email per car, at most once a month per item.
                $due = $vehicle->reminders->filter(fn (Reminder $r) => in_array($r->status($vehicle->current_mileage), [Reminder::OVERDUE, Reminder::DUE_SOON], true)
                    && ($r->notified_at === null || $r->notified_at->lt(now()->subDays(30))));

                if ($due->isNotEmpty() && $vehicle->owner) {
                    $vehicle->owner->notify(new MaintenanceDue($vehicle, $due->values()));
                    Reminder::whereKey($due->modelKeys())->update(['notified_at' => now()]);
                    $maintenance++;
                }
            }
        });

        $documents = Document::with('vehicle.owner')
            ->whereNotNull('expires_on')
            ->where('expires_on', '<=', now()->addDays(30))
            ->where('expires_on', '>=', now()->subDays(7))
            ->whereNull('expiry_notified_at')
            ->get()
            ->each(function (Document $document) {
                $document->vehicle->owner?->notify(new DocumentExpiring($document));
                $document->update(['expiry_notified_at' => now()]);
            });

        $this->components->info("Maintenance emails: {$maintenance}. Expiry emails: {$documents->count()}.");

        return self::SUCCESS;
    }
}
