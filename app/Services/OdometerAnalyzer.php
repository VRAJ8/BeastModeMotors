<?php

namespace App\Services;

use App\Models\OdometerReading;
use Illuminate\Support\Collection;

/**
 * Finds odometer readings that go backwards — the classic sign of a rollback (or a typo).
 */
class OdometerAnalyzer
{
    /**
     * @param  Collection<int, OdometerReading>  $readings
     * @return list<array{date: string, reading: int, previous_max: int}>
     */
    public function anomalies(Collection $readings): array
    {
        $max = 0;
        $anomalies = [];

        foreach ($this->sorted($readings) as $reading) {
            if ($reading->reading < $max) {
                $anomalies[] = [
                    'id' => $reading->getKey(),
                    'date' => $reading->recorded_on->toDateString(),
                    'reading' => $reading->reading,
                    'previous_max' => $max,
                ];
            }

            $max = max($max, $reading->reading);
        }

        return $anomalies;
    }

    /**
     * Average miles driven per year across the recorded history.
     *
     * @param  Collection<int, OdometerReading>  $readings
     */
    public function milesPerYear(Collection $readings): ?int
    {
        $sorted = $this->sorted($readings);

        if ($sorted->count() < 2) {
            return null;
        }

        $days = $sorted->first()->recorded_on->diffInDays($sorted->last()->recorded_on);

        return $days < 60 ? null : (int) round(($sorted->max('reading') - $sorted->first()->reading) / $days * 365);
    }

    /**
     * @param  Collection<int, OdometerReading>  $readings
     * @return Collection<int, OdometerReading>
     */
    private function sorted(Collection $readings): Collection
    {
        return $readings->sortBy([['recorded_on', 'asc'], ['id', 'asc']])->values();
    }
}
