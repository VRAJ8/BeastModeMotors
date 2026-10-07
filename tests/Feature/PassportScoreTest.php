<?php

use App\Models\ServiceRecord;
use App\Services\PassportScore;

function score($vehicle, string $key): int
{
    return collect(app(PassportScore::class)->for($vehicle->fresh())['components'])->firstWhere('key', $key)['points'];
}

it('gives a car with no history a thin score and useful tips', function () {
    $result = app(PassportScore::class)->for(car());

    expect($result['grade'])->toBe('D')
        ->and(collect($result['components'])->pluck('tip')->filter())->not->toBeEmpty();
});

it('weights verified records above receipts above self-reported ones', function () {
    [$verified, $self] = [car(), car()];
    ServiceRecord::factory()->verified()->create(['vehicle_id' => $verified->id]);
    ServiceRecord::factory()->create(['vehicle_id' => $self->id]);

    expect(score($verified, 'evidence'))->toBe(30)->and(score($self, 'evidence'))->toBe(9);
});

it('discounts self-reported records logged long after the work', function () {
    $vehicle = car();
    $record = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'performed_on' => now()->subYear()]);

    expect($record->isBackfilled())->toBeTrue()->and(score($vehicle, 'evidence'))->toBe(5);
});

it('penalises an odometer that goes backwards', function () {
    $vehicle = car(['current_mileage' => 30000]);
    $before = score($vehicle, 'odometer');
    $vehicle->readings()->create(['reading' => 25000, 'recorded_on' => now(), 'source' => 'manual']);

    expect($before)->toBe(15)->and(score($vehicle, 'odometer'))->toBe(5);
});

it('penalises open recalls', function () {
    $vehicle = car();
    $vehicle->recalls()->create(['campaign_number' => '24V001', 'component' => 'Brakes', 'summary' => 'x']);

    expect(score($vehicle, 'upkeep'))->toBe(8);
});

it('measures coverage across the years the car has been documented', function () {
    $vehicle = car();
    $vehicle->ownerships()->update(['started_on' => now()->subYears(4)]);
    ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'performed_on' => now()->subMonths(2)]);
    ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'performed_on' => now()->subMonths(14)]);

    expect(score($vehicle, 'coverage'))->toBe(13);
});
