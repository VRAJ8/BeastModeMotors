<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A recurring maintenance task, due by mileage, by time, or whichever comes first.
 */
class Reminder extends Model
{
    public const OK = 'ok';

    public const DUE_SOON = 'due_soon';

    public const OVERDUE = 'overdue';

    public const UNKNOWN = 'unknown';

    public const SOON_MILES = 1000;

    public const SOON_DAYS = 30;

    protected $fillable = [
        'vehicle_id', 'task', 'interval_miles', 'interval_months', 'last_done_on', 'last_done_mileage', 'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'last_done_on' => 'date',
            'notified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function dueMileage(): ?int
    {
        return $this->interval_miles && $this->last_done_mileage !== null
            ? $this->last_done_mileage + $this->interval_miles
            : null;
    }

    public function dueDate(): ?Carbon
    {
        return $this->interval_months && $this->last_done_on
            ? $this->last_done_on->copy()->addMonthsNoOverflow($this->interval_months)
            : null;
    }

    public function status(?int $currentMileage = null): string
    {
        if ($this->last_done_on === null && $this->last_done_mileage === null) {
            return self::UNKNOWN;
        }

        $mileage = $currentMileage ?? $this->vehicle->current_mileage;
        $dueMileage = $this->dueMileage();
        $dueDate = $this->dueDate();

        if (($dueMileage !== null && $mileage >= $dueMileage) || ($dueDate?->isPast())) {
            return self::OVERDUE;
        }

        if (($dueMileage !== null && $mileage >= $dueMileage - self::SOON_MILES)
            || ($dueDate !== null && $dueDate->lte(now()->addDays(self::SOON_DAYS)))) {
            return self::DUE_SOON;
        }

        return self::OK;
    }

    /**
     * How far through the interval the car is (0–100), using whichever limit is closer.
     */
    public function progress(?int $currentMileage = null): int
    {
        $mileage = $currentMileage ?? $this->vehicle->current_mileage;
        $parts = [];

        if ($this->dueMileage() !== null) {
            $parts[] = ($mileage - $this->last_done_mileage) / $this->interval_miles;
        }

        if ($this->dueDate() !== null) {
            $parts[] = $this->last_done_on->diffInDays(now()) / max(1, $this->last_done_on->diffInDays($this->dueDate()));
        }

        return $parts ? (int) min(100, max(0, round(max($parts) * 100))) : 0;
    }

    public function intervalLabel(): string
    {
        return collect([
            $this->interval_miles ? number_format($this->interval_miles).' mi' : null,
            $this->interval_months ? $this->interval_months.' months' : null,
        ])->filter()->implode(' or ') ?: 'One-off';
    }

    public function dueLabel(?int $currentMileage = null): string
    {
        if ($this->status($currentMileage) === self::UNKNOWN) {
            return 'No record yet';
        }

        return collect([
            $this->dueMileage() !== null ? number_format($this->dueMileage()).' mi' : null,
            $this->dueDate()?->format('M j, Y'),
        ])->filter()->implode(' or ');
    }
}
