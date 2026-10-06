<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Services\RecallSync;
use Illuminate\Console\Command;
use Throwable;

class SyncRecalls extends Command
{
    protected $signature = 'passport:sync-recalls {--force : Check every car, even if checked recently}';

    protected $description = 'Check NHTSA for new safety recalls on every car and notify owners';

    public function handle(RecallSync $sync): int
    {
        $found = 0;
        $failed = 0;

        Vehicle::with('owner')
            ->when(! $this->option('force'), fn ($q) => $q->where(fn ($q) => $q->whereNull('recalls_checked_at')->orWhere('recalls_checked_at', '<', now()->subDays(6))))
            ->chunkById(50, function ($vehicles) use ($sync, &$found, &$failed) {
                foreach ($vehicles as $vehicle) {
                    try {
                        $new = $sync->sync($vehicle);
                    } catch (Throwable $e) {
                        report($e);
                        $new = null;
                    }

                    $new === null ? $failed++ : $found += $new->count();
                }
            });

        $this->components->info("New recalls: {$found}. Lookups failed: {$failed}.");

        return self::SUCCESS;
    }
}
