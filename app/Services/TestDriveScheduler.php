<?php

namespace App\Services;

use App\Models\TestDrive;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Works out which test-drive slots are open for a vehicle, honouring
 * showroom hours, minimum notice, the booking window and existing bookings.
 */
class TestDriveScheduler
{
    /**
     * @return Collection<int, CarbonImmutable>
     */
    public function availableSlots(Vehicle $vehicle, CarbonInterface $date): Collection
    {
        $date = CarbonImmutable::instance($date)->startOfDay();

        if (! $this->isBookableDate($date)) {
            return collect();
        }

        [$open, $close] = $this->hoursFor($date);
        $step = (int) config('dealership.test_drives.slot_minutes');
        $earliest = now()->addHours((int) config('dealership.test_drives.min_notice_hours'));

        $taken = TestDrive::query()
            ->where('vehicle_id', $vehicle->id)
            ->active()
            ->whereBetween('scheduled_at', [$date, $date->endOfDay()])
            ->pluck('scheduled_at')
            ->map(fn ($at) => CarbonImmutable::parse($at)->format('H:i'))
            ->all();

        $slots = collect();
        for ($slot = $date->setTimeFromTimeString($open); $slot->lt($date->setTimeFromTimeString($close)); $slot = $slot->addMinutes($step)) {
            if ($slot->gte($earliest) && ! in_array($slot->format('H:i'), $taken, true)) {
                $slots->push($slot);
            }
        }

        return $slots;
    }

    public function isSlotAvailable(Vehicle $vehicle, CarbonInterface $at): bool
    {
        return $this->availableSlots($vehicle, $at)
            ->contains(fn (CarbonImmutable $slot) => $slot->equalTo($at));
    }

    public function isBookableDate(CarbonInterface $date): bool
    {
        $date = CarbonImmutable::instance($date)->startOfDay();
        $lastDay = now()->startOfDay()->addDays((int) config('dealership.test_drives.booking_window_days'));

        return $date->gte(now()->startOfDay())
            && $date->lte($lastDay)
            && $this->hoursFor($date) !== null;
    }

    /**
     * Upcoming dates the showroom is open, for the date picker.
     *
     * @return Collection<int, CarbonImmutable>
     */
    public function bookableDates(int $limit = 14): Collection
    {
        $dates = collect();
        $day = CarbonImmutable::today();
        $lastDay = $day->addDays((int) config('dealership.test_drives.booking_window_days'));

        while ($dates->count() < $limit && $day->lte($lastDay)) {
            if ($this->hoursFor($day) !== null) {
                $dates->push($day);
            }
            $day = $day->addDay();
        }

        return $dates;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    protected function hoursFor(CarbonInterface $date): ?array
    {
        return config('dealership.test_drives.hours')[$date->isoWeekday()] ?? null;
    }
}
