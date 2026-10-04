<?php

use App\Models\Reminder;

function reminder(array $attributes): Reminder
{
    return new Reminder($attributes + ['task' => 'Engine oil & filter', 'interval_miles' => 7500, 'interval_months' => 12]);
}

it('is unknown until it has been done once', function () {
    expect(reminder([])->status(30000))->toBe(Reminder::UNKNOWN);
});

it('is due by mileage or by time, whichever comes first', function () {
    $byMiles = reminder(['last_done_on' => now()->subMonth(), 'last_done_mileage' => 20000]);
    $byTime = reminder(['last_done_on' => now()->subMonths(13), 'last_done_mileage' => 29000]);

    expect($byMiles->status(27600))->toBe(Reminder::OVERDUE)
        ->and($byMiles->status(26800))->toBe(Reminder::DUE_SOON)
        ->and($byMiles->status(22000))->toBe(Reminder::OK)
        ->and($byTime->status(29500))->toBe(Reminder::OVERDUE);
});

it('reports progress through the interval', function () {
    $r = reminder(['last_done_on' => now(), 'last_done_mileage' => 10000]);

    expect($r->progress(13750))->toBe(50)->and($r->dueMileage())->toBe(17500);
});
