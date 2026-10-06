<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\ServiceRecord;
use App\Models\Vehicle;

/**
 * Scores how trustworthy a car's history is, out of 100, with the reasoning shown.
 *
 * The score rewards proof, not spending: a car with a short, verified history beats one with a long,
 * self-reported one. Every component explains itself and suggests how to improve.
 */
class PassportScore
{
    /** Evidence weight per record, by strength of proof. */
    private const EVIDENCE_WEIGHTS = [
        ServiceRecord::EVIDENCE_VERIFIED => 1.0,
        ServiceRecord::EVIDENCE_DOCUMENTED => 0.7,
        ServiceRecord::EVIDENCE_SELF => 0.3,
        ServiceRecord::EVIDENCE_DISPUTED => 0.0,
    ];

    private const BACKFILL_FACTOR = 0.6;

    public function __construct(private OdometerAnalyzer $odometer) {}

    /**
     * @return array{total: int, grade: string, label: string, components: list<array{key: string, label: string, points: int, max: int, detail: string, tip: ?string}>}
     */
    public function for(Vehicle $vehicle): array
    {
        $vehicle->loadMissing(['records' => fn ($q) => $q->withCount('documents'), 'readings', 'reminders', 'recalls', 'ownerships', 'photos']);

        $components = [
            $this->identity($vehicle),
            $this->coverage($vehicle),
            $this->evidence($vehicle),
            $this->odometerIntegrity($vehicle),
            $this->upkeep($vehicle),
        ];

        $total = (int) array_sum(array_column($components, 'points'));
        [$grade, $label] = self::grade($total);

        return compact('total', 'grade', 'label', 'components');
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function grade(int $total): array
    {
        return match (true) {
            $total >= 85 => ['A', 'Excellent history'],
            $total >= 70 => ['B', 'Strong history'],
            $total >= 50 => ['C', 'Fair history'],
            default => ['D', 'Thin history'],
        };
    }

    private function identity(Vehicle $vehicle): array
    {
        $points = ($vehicle->vin_valid ? 10 : 0) + ($vehicle->photos->isNotEmpty() ? 5 : 0);

        return $this->component('identity', 'Identity', $points, 15,
            ($vehicle->vin_valid ? 'VIN check digit verified' : 'VIN failed the check-digit test').' · '.
            ($vehicle->photos->isNotEmpty() ? $vehicle->photos->count().' photos' : 'no photos'),
            match (true) {
                ! $vehicle->vin_valid => 'Double-check the VIN against the door-jamb sticker.',
                $vehicle->photos->isEmpty() => 'Add a few photos so buyers can match the car to its passport.',
                default => null,
            });
    }

    /**
     * Share of the car's documented life (in 12-month windows, up to 10) with at least one record.
     * A record the shop disputed proves nothing, so it fills no gap.
     */
    private function coverage(Vehicle $vehicle): array
    {
        $records = $vehicle->records->reject(fn (ServiceRecord $r) => $r->evidence() === ServiceRecord::EVIDENCE_DISPUTED);

        if ($records->isEmpty()) {
            return $this->component('coverage', 'History coverage', 0, 25, $vehicle->records->isEmpty() ? 'No records yet' : 'Only disputed records', 'Log the most recent service you can find a receipt for.');
        }

        $start = collect([$records->min('performed_on'), $vehicle->ownerships->min('started_on')])->filter()->min();
        // Round, so a car with 13 months of history isn't judged on a second year it barely lived.
        $windows = (int) min(10, max(1, round(floor($start->diffInMonths(now())) / 12)));

        $covered = collect(range(0, $windows - 1))->filter(function (int $i) use ($records) {
            $to = now()->subYears($i);
            $from = $to->copy()->subYear();

            return $records->contains(fn (ServiceRecord $r) => $r->performed_on->between($from, $to));
        })->count();

        $missing = $windows - $covered;

        return $this->component('coverage', 'History coverage', (int) round(25 * $covered / $windows), 25,
            "Records in {$covered} of the last {$windows} ".str('year')->plural($windows),
            $missing > 0 ? "Fill the {$missing} ".str('year')->plural($missing).' with no records — old invoices from the shop count.' : null);
    }

    private function evidence(Vehicle $vehicle): array
    {
        $records = $vehicle->records;

        if ($records->isEmpty()) {
            return $this->component('evidence', 'Quality of evidence', 0, 30, 'Nothing to weigh yet', 'Attach receipts to your records.');
        }

        $counts = $records->countBy(fn (ServiceRecord $r) => $r->evidence());
        $weight = $records->avg(function (ServiceRecord $record) {
            $weight = self::EVIDENCE_WEIGHTS[$record->evidence()];

            return $record->evidence() !== ServiceRecord::EVIDENCE_VERIFIED && $record->isBackfilled()
                ? $weight * self::BACKFILL_FACTOR
                : $weight;
        });

        $detail = collect([
            ServiceRecord::EVIDENCE_VERIFIED => 'shop-verified',
            ServiceRecord::EVIDENCE_DOCUMENTED => 'with receipts',
            ServiceRecord::EVIDENCE_SELF => 'self-reported',
            ServiceRecord::EVIDENCE_DISPUTED => 'disputed',
        ])->filter(fn ($label, $key) => $counts->has($key))->map(fn ($label, $key) => $counts[$key].' '.$label)->implode(' · ');

        return $this->component('evidence', 'Quality of evidence', (int) round(30 * $weight), 30, $detail,
            match (true) {
                $counts->get(ServiceRecord::EVIDENCE_SELF, 0) > 0 => 'Attach receipts to self-reported records, or ask the shop to verify them.',
                $counts->get(ServiceRecord::EVIDENCE_DOCUMENTED, 0) > 0 => 'Ask your shop to confirm records with one click — verified records count most.',
                default => null,
            });
    }

    private function odometerIntegrity(Vehicle $vehicle): array
    {
        $anomalies = $this->odometer->anomalies($vehicle->readings);
        $latest = $vehicle->readings->max('recorded_on');
        $recent = $latest !== null && $latest->gte(now()->subMonths(6));

        $points = ($anomalies === [] ? 10 : 0) + ($recent ? 5 : 0);

        return $this->component('odometer', 'Odometer integrity', $points, 15,
            ($anomalies === [] ? 'Readings only ever go up' : count($anomalies).' reading(s) lower than an earlier one').' · '.
            ($latest ? 'last reading '.$latest->diffForHumans() : 'no readings'),
            match (true) {
                $anomalies !== [] => 'Check the flagged readings — a typo looks the same as a rollback to a buyer.',
                ! $recent => 'Add a current odometer reading.',
                default => null,
            });
    }

    private function upkeep(Vehicle $vehicle): array
    {
        $overdue = $vehicle->reminders->filter(fn (Reminder $r) => $r->status($vehicle->current_mileage) === Reminder::OVERDUE)->count();
        $openRecalls = $vehicle->recalls->filter->isOpen()->count();

        $points = max(0, 8 - 3 * $overdue) + ($openRecalls === 0 ? 7 : 0);

        return $this->component('upkeep', 'Upkeep & recalls', $points, 15,
            ($overdue ? "{$overdue} maintenance item(s) overdue" : 'Maintenance up to date').' · '.
            ($openRecalls ? "{$openRecalls} open recall(s)" : 'no open recalls'),
            match (true) {
                $openRecalls > 0 => 'Recall repairs are free at any franchise dealer — get them done and log them.',
                $overdue > 0 => 'Catch up on overdue maintenance, or log it if it was already done.',
                default => null,
            });
    }

    private function component(string $key, string $label, int $points, int $max, string $detail, ?string $tip): array
    {
        return ['key' => $key, 'label' => $label, 'points' => min($points, $max), 'max' => $max, 'detail' => $detail, 'tip' => $tip];
    }
}
