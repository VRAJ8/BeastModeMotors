<?php

use App\Livewire\AddVehicle;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(fn () => $this->user = User::factory()->create());

function nhtsaFake(array $row = []): void
{
    Http::fake([
        'vpic.nhtsa.dot.gov/*' => Http::response(['Results' => [$row + [
            'ModelYear' => '2021', 'Make' => 'TESLA', 'Model' => 'Model 3', 'Trim' => 'Long Range',
            'BodyClass' => 'Sedan/Saloon', 'DriveType' => 'AWD/All-Wheel Drive', 'FuelTypePrimary' => 'Electric',
            'ElectrificationLevel' => 'BEV (Battery Electric Vehicle)', 'PlantCountry' => 'UNITED STATES (USA)',
        ]]]),
        'api.nhtsa.gov/*' => Http::response(['Count' => 1, 'results' => [[
            'NHTSACampaignNumber' => '23V838000', 'Component' => 'STEERING', 'Summary' => 'Steering may detach.',
            'Consequence' => 'Crash risk.', 'Remedy' => 'Dealer fix.', 'ReportReceivedDate' => '05/12/2023',
        ]]]),
    ]);
}

it('decodes the VIN with NHTSA and prefills the details', function () {
    nhtsaFake();

    Livewire::actingAs($this->user)->test(AddVehicle::class)
        ->set('vin', '5yj3e1eb0mf123456')
        ->call('decode')
        ->assertHasNoErrors()
        ->assertSet('step', 2)
        ->assertSet('make', 'Tesla')
        ->assertSet('model', 'Model 3')
        ->assertSet('fuel_type', 'electric');
});

it('falls back to the offline decoder when NHTSA is unreachable', function () {
    Http::fake(['*' => Http::response(null, 500)]);

    Livewire::actingAs($this->user)->test(AddVehicle::class)
        ->set('vin', '1HGCM82633A004352')
        ->call('decode')
        ->assertSet('step', 2)
        ->assertSet('make', 'Honda')
        ->assertSet('year', 2003)
        ->assertSet('lookup.source', 'offline');
});

it('rejects malformed VINs', function () {
    Livewire::actingAs($this->user)->test(AddVehicle::class)
        ->set('vin', '1HGCM82633AO04352')
        ->call('decode')
        ->assertHasErrors('vin');
});

it('refuses a car that already has a passport with someone else', function () {
    $existing = car();

    Livewire::actingAs($this->user)->test(AddVehicle::class)
        ->set('vin', $existing->vin)
        ->call('decode')
        ->assertHasErrors('vin')
        ->assertSet('step', 1);
});

it('creates the car, ownership, odometer history, maintenance plan and recalls', function () {
    nhtsaFake();

    Livewire::actingAs($this->user)->test(AddVehicle::class)
        ->set('vin', '5YJ3E1EB0MF123456')
        ->call('decode')
        ->call('confirmDetails')
        ->set('acquired_via', 'new')
        ->set('started_on', now()->subYear()->toDateString())
        ->set('start_mileage', 10)
        ->set('current_mileage', 12000)
        ->set('purchase_price', '48,990')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $vehicle = Vehicle::firstWhere('vin', '5YJ3E1EB0MF123456');

    expect($vehicle->user_id)->toBe($this->user->id)
        ->and($vehicle->current_mileage)->toBe(12000)
        ->and($vehicle->ownerships()->first()->purchase_price_cents)->toBe(4899000)
        ->and($vehicle->readings()->count())->toBe(2)
        ->and($vehicle->reminders()->pluck('task'))->not->toContain('Engine oil & filter')
        ->and($vehicle->reminders()->pluck('task'))->toContain('Battery coolant check')
        ->and($vehicle->recalls()->first()->campaign_number)->toBe('23V838000')
        ->and($vehicle->recalls()->first()->reported_on->toDateString())->toBe('2023-12-05');
});

it('does not accept a current mileage below the purchase mileage', function () {
    Http::fake(['*' => Http::response(null, 500)]);

    Livewire::actingAs($this->user)->test(AddVehicle::class)
        ->set('vin', '1HGCM82633A004352')
        ->call('decode')
        ->set('model', 'Accord')
        ->call('confirmDetails')
        ->set('start_mileage', 50000)
        ->set('current_mileage', 40000)
        ->call('save')
        ->assertHasErrors('current_mileage');
});
