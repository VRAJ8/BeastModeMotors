<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\Ownership;
use App\Models\ServiceRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * What the car has cost its current owner: running costs, maintenance, cost per mile, fuel economy.
 */
class CostReport
{
    /**
     * @return array{total: int, maintenance: int, by_category: array<string, int>, months: int, per_month: int, miles: int, per_mile: ?float, monthly: list<array{label: string, value: int}>, economy: ?array{value: float, unit: string, fills: int}}
     */
    public function for(Ownership $ownership): array
    {
        $vehicle = $ownership->vehicle;
        $expenses = Expense::where('ownership_id', $ownership->getKey())->get();
        // Older invoices the owner backfilled are the car's history, not what it has cost them.
        $records = ServiceRecord::where('ownership_id', $ownership->getKey())
            ->whereDate('performed_on', '>=', $ownership->started_on)
            ->get(['performed_on', 'cost_cents']);

        $maintenance = (int) $records->sum('cost_cents');
        $byCategory = collect(['maintenance' => $maintenance])
            ->merge($expenses->groupBy(fn (Expense $e) => $e->category->value)->map(fn ($group) => (int) $group->sum('amount_cents')))
            ->filter()
            ->sortDesc()
            ->all();

        $total = array_sum($byCategory);
        $months = $ownership->months();
        $miles = max(0, $vehicle->current_mileage - $ownership->start_mileage);

        $monthly = collect(range(11, 0))->map(function (int $ago) use ($expenses, $records) {
            $month = Carbon::now()->startOfMonth()->subMonthsNoOverflow($ago);
            $inMonth = fn (Carbon $date) => $date->isSameMonth($month);

            return [
                'label' => $month->format('M'),
                'value' => (int) ($expenses->filter(fn (Expense $e) => $inMonth($e->spent_on))->sum('amount_cents')
                    + $records->filter(fn (ServiceRecord $r) => $inMonth($r->performed_on))->sum('cost_cents')),
            ];
        })->all();

        return [
            'total' => $total,
            'maintenance' => $maintenance,
            'by_category' => $byCategory,
            'months' => $months,
            'per_month' => (int) round($total / $months),
            'miles' => $miles,
            'per_mile' => $miles > 0 ? round($total / $miles / 100, 2) : null,
            'monthly' => $monthly,
            'economy' => $this->economy($expenses),
        ];
    }

    /**
     * Full-tank method: distance between the first and last fills with an odometer reading, divided by
     * everything put in after the first — including fills logged without a reading, which still burned fuel.
     *
     * @param  Collection<int, Expense>  $expenses
     * @return array{value: float, unit: string, fills: int}|null
     */
    private function economy(Collection $expenses): ?array
    {
        foreach ([[ExpenseCategory::Fuel, 'mpg'], [ExpenseCategory::Charging, 'mi/kWh']] as [$category, $unit]) {
            $fills = $expenses
                ->filter(fn (Expense $e) => $e->category === $category && $e->volume > 0)
                ->sort(fn (Expense $a, Expense $b) => [$a->spent_on, $a->odometer ?? PHP_INT_MAX, $a->id] <=> [$b->spent_on, $b->odometer ?? PHP_INT_MAX, $b->id])
                ->values();

            $first = $fills->search(fn (Expense $e) => $e->odometer !== null);
            $last = $fills->reverse()->search(fn (Expense $e) => $e->odometer !== null);

            if ($first === false || $last === false || $last <= $first) {
                continue;
            }

            $distance = $fills[$last]->odometer - $fills[$first]->odometer;
            $volume = $fills->slice($first + 1, $last - $first)->sum('volume');

            if ($distance > 0 && $volume > 0) {
                return ['value' => round($distance / $volume, 1), 'unit' => $unit, 'fills' => $last - $first + 1];
            }
        }

        return null;
    }
}
