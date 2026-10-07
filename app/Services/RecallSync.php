<?php

namespace App\Services;

use App\Models\Recall;
use App\Models\Vehicle;
use App\Notifications\RecallsFound;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecallSync
{
    public function __construct(private Nhtsa $nhtsa) {}

    /**
     * Pull recalls for the car's make/model/year and store any new ones.
     *
     * @return Collection<int, Recall>|null New recalls, or null when NHTSA couldn't be reached.
     */
    public function sync(Vehicle $vehicle, bool $notify = true): ?Collection
    {
        $results = $this->nhtsa->recalls($vehicle->make, $vehicle->model, $vehicle->year);

        if ($results === null) {
            return null;
        }

        // The first successful check finds every campaign ever issued for the model: those aren't news.
        $notify = $notify && $vehicle->recalls_checked_at !== null;

        return DB::transaction(function () use ($vehicle, $results, $notify) {
            $known = $vehicle->recalls()->pluck('campaign_number')->all();

            $new = collect($results)
                ->reject(fn (array $row) => in_array($row['campaign_number'], $known, true))
                ->map(fn (array $row) => $vehicle->recalls()->create($row))
                ->values();

            $vehicle->forceFill(['recalls_checked_at' => now()])->saveQuietly();

            if ($notify && $new->isNotEmpty() && $vehicle->owner) {
                $vehicle->owner->notify(new RecallsFound($vehicle, $new));
            }

            return $new;
        });
    }
}
